<?php

declare(strict_types=1);

namespace App\Services\Schools;

use Throwable;

class ClamAvSchoolLessonResourceScanner implements SchoolLessonResourceScanner
{
    public function scan(string $absolutePath): SchoolLessonResourceScanResult
    {
        $socketPath = config('services.clamav.socket');
        $timeoutSeconds = max(1, (int) config('services.clamav.timeout_seconds', 30));

        if (! is_string($socketPath) || $socketPath === '' || ! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Unavailable, 'clamav');
        }

        $errorCode = 0;
        $errorMessage = '';
        $socket = @stream_socket_client(
            'unix://'.$socketPath,
            $errorCode,
            $errorMessage,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
        );

        if (! is_resource($socket)) {
            return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Unavailable, 'clamav');
        }

        stream_set_timeout($socket, $timeoutSeconds);
        $file = @fopen($absolutePath, 'rb');

        if (! is_resource($file)) {
            fclose($socket);

            return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Unavailable, 'clamav');
        }

        try {
            $this->writeAll($socket, "zINSTREAM\0");

            while (! feof($file)) {
                $chunk = fread($file, 65536);

                if ($chunk === false) {
                    return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Unavailable, 'clamav');
                }

                if ($chunk === '') {
                    continue;
                }

                $this->writeAll($socket, pack('N', strlen($chunk)).$chunk);
            }

            $this->writeAll($socket, pack('N', 0));
            $response = $this->readResponse($socket);

            if ($response === null) {
                return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Unavailable, 'clamav');
            }

            if (str_ends_with($response, ' OK')) {
                return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Clean, 'clamav');
            }

            if (str_contains($response, ' FOUND')) {
                return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Infected, 'clamav');
            }

            return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Unavailable, 'clamav');
        } catch (Throwable) {
            return new SchoolLessonResourceScanResult(SchoolLessonResourceScanVerdict::Unavailable, 'clamav');
        } finally {
            fclose($file);
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function writeAll($socket, string $contents): void
    {
        $offset = 0;
        $length = strlen($contents);

        while ($offset < $length) {
            $written = fwrite($socket, substr($contents, $offset));

            if ($written === false || $written === 0) {
                throw new \RuntimeException('ClamAV socket write failed.');
            }

            $offset += $written;
        }
    }

    /** @param resource $socket */
    private function readResponse($socket): ?string
    {
        $response = '';

        while (strlen($response) < 4096) {
            $chunk = fread($socket, 1024);

            if ($chunk === false || $chunk === '') {
                $metadata = stream_get_meta_data($socket);

                return ($metadata['timed_out'] ?? false) ? null : ($response !== '' ? $response : null);
            }

            $response .= $chunk;
            $terminator = strpos($response, "\0");

            if ($terminator !== false) {
                return substr($response, 0, $terminator);
            }
        }

        return null;
    }
}
