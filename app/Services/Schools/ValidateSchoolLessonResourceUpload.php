<?php

declare(strict_types=1);

namespace App\Services\Schools;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ValidateSchoolLessonResourceUpload
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MAX_DOCX_ENTRIES = 256;

    private const MAX_DOCX_UNCOMPRESSED_BYTES = 50 * 1024 * 1024;

    public function handle(UploadedFile $file): ValidatedSchoolLessonUpload
    {
        if (! $file->isValid() || $file->getSize() === false || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['upload' => 'The upload must be a valid file no larger than 10 MB.']);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png'], true)) {
            throw ValidationException::withMessages(['upload' => 'Use a PDF, DOCX, TXT, JPEG, or PNG file.']);
        }

        $mimeType = $file->getMimeType();
        $sourcePath = $file->getRealPath();
        if ($mimeType === false || $sourcePath === false) {
            throw ValidationException::withMessages(['upload' => 'The uploaded file could not be inspected.']);
        }

        return match ($extension) {
            'jpg', 'jpeg', 'png' => $this->sanitizeImage($sourcePath, $extension),
            'pdf' => $this->validatePdf($sourcePath, $mimeType),
            'txt' => $this->validateText($sourcePath, $mimeType),
            'docx' => $this->validateDocx($sourcePath, $mimeType),
        };
    }

    private function validatePdf(string $path, string $mimeType): ValidatedSchoolLessonUpload
    {
        $pdfBytes = file_get_contents($path);
        if ($mimeType !== 'application/pdf'
            || ! is_string($pdfBytes)
            || ! str_starts_with($pdfBytes, '%PDF-')
            || ! str_contains(substr($pdfBytes, -2048), '%%EOF')
            || str_contains($pdfBytes, '/Encrypt')) {
            throw ValidationException::withMessages(['upload' => 'The PDF file is malformed or has an unexpected type.']);
        }

        return new ValidatedSchoolLessonUpload($path, 'pdf', 'application/pdf', false);
    }

    private function validateText(string $path, string $mimeType): ValidatedSchoolLessonUpload
    {
        $contents = file_get_contents($path);
        if ($mimeType !== 'text/plain'
            || ! is_string($contents)
            || str_contains($contents, "\0")
            || ! mb_check_encoding($contents, 'UTF-8')) {
            throw ValidationException::withMessages(['upload' => 'The TXT file is malformed or has an unexpected type.']);
        }

        return new ValidatedSchoolLessonUpload($path, 'txt', 'text/plain', false);
    }

    private function validateDocx(string $path, string $mimeType): ValidatedSchoolLessonUpload
    {
        if (! in_array($mimeType, [
            'application/zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true)) {
            throw ValidationException::withMessages(['upload' => 'The DOCX file is malformed or has an unexpected type.']);
        }

        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['upload' => 'DOCX validation is unavailable on this server.']);
        }

        $archive = new ZipArchive;
        if ($archive->open($path) !== true || $archive->numFiles > self::MAX_DOCX_ENTRIES) {
            throw ValidationException::withMessages(['upload' => 'The DOCX file is malformed or contains too many parts.']);
        }

        $totalUncompressedBytes = 0;
        $requiredParts = ['[Content_Types].xml' => false, 'word/document.xml' => false];

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $part = $archive->statIndex($index);
            if (! is_array($part) || ! isset($part['name'], $part['size'])) {
                $archive->close();

                throw ValidationException::withMessages(['upload' => 'The DOCX file contains an invalid part.']);
            }

            $name = (string) $part['name'];
            if (str_starts_with($name, '/') || str_contains($name, '../')
                || str_starts_with($name, 'word/embeddings/')
                || str_starts_with($name, 'word/activeX/')) {
                $archive->close();

                throw ValidationException::withMessages(['upload' => 'The DOCX file contains an unsupported part.']);
            }

            if (($part['encryption_method'] ?? 0) !== 0) {
                $archive->close();

                throw ValidationException::withMessages(['upload' => 'Encrypted DOCX files are not accepted.']);
            }

            if ((int) $part['size'] > self::MAX_DOCX_UNCOMPRESSED_BYTES - $totalUncompressedBytes) {
                $archive->close();

                throw ValidationException::withMessages(['upload' => 'The DOCX expands beyond the supported size.']);
            }

            $partStream = $archive->getStream($name);
            if (! is_resource($partStream)) {
                $archive->close();

                throw ValidationException::withMessages(['upload' => 'Encrypted or unreadable DOCX parts are not accepted.']);
            }

            $partBytes = 0;
            while (! feof($partStream)) {
                $partChunk = fread($partStream, 8192);
                if ($partChunk === false) {
                    fclose($partStream);
                    $archive->close();

                    throw ValidationException::withMessages(['upload' => 'The DOCX file contains an unreadable part.']);
                }

                $partBytes += strlen($partChunk);
                if ($partBytes > self::MAX_DOCX_UNCOMPRESSED_BYTES - $totalUncompressedBytes) {
                    fclose($partStream);
                    $archive->close();

                    throw ValidationException::withMessages(['upload' => 'The DOCX expands beyond the supported size.']);
                }
            }
            fclose($partStream);
            $totalUncompressedBytes += $partBytes;

            if (array_key_exists($name, $requiredParts)) {
                $requiredParts[$name] = true;
            }
        }

        $archive->close();
        if (in_array(false, $requiredParts, true)) {
            throw ValidationException::withMessages(['upload' => 'The DOCX file is missing required document parts.']);
        }

        return new ValidatedSchoolLessonUpload($path, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', false);
    }

    private function sanitizeImage(string $path, string $extension): ValidatedSchoolLessonUpload
    {
        $imageInfo = @getimagesize($path);
        if (! is_array($imageInfo) || ! isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])
            || $imageInfo[0] > 4000 || $imageInfo[1] > 4000 || ($imageInfo[0] * $imageInfo[1]) > 16_000_000) {
            throw ValidationException::withMessages(['upload' => 'The image is invalid or exceeds the supported dimensions.']);
        }

        $expectedMimeType = $extension === 'png' ? 'image/png' : 'image/jpeg';
        if ($imageInfo['mime'] !== $expectedMimeType) {
            throw ValidationException::withMessages(['upload' => 'The image extension does not match its contents.']);
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));
        $temporaryPath = tempnam(sys_get_temp_dir(), 'nova-lesson-image-');
        if ($image === false || $temporaryPath === false) {
            if (is_resource($image) || $image instanceof \GdImage) {
                imagedestroy($image);
            }

            if (is_string($temporaryPath)) {
                @unlink($temporaryPath);
            }

            throw ValidationException::withMessages(['upload' => 'The image could not be safely re-encoded.']);
        }

        $encoded = $extension === 'png' ? imagepng($image, $temporaryPath, 6) : imagejpeg($image, $temporaryPath, 88);
        imagedestroy($image);

        if (! $encoded || filesize($temporaryPath) === false || filesize($temporaryPath) > self::MAX_BYTES) {
            @unlink($temporaryPath);

            throw ValidationException::withMessages(['upload' => 'The re-encoded image exceeds the 10 MB limit.']);
        }

        return new ValidatedSchoolLessonUpload($temporaryPath, $extension, $expectedMimeType, true);
    }
}
