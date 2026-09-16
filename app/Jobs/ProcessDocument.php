<?php

namespace App\Jobs;

use App\Models\Document;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public Document $document) {}

    public function failed(?Throwable $exception): void
    {
        Document::query()->whereKey($this->document->id)->whereIn('status', ['uploaded', 'extracting'])->update([
            'status' => 'failed',
            'failure_code' => 'extraction_failed',
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $document = $this->document->fresh();

        if ($document === null || $document->status !== 'uploaded') {
            return;
        }

        $document->forceFill(['status' => 'extracting', 'failure_code' => null])->save();
        $stream = Storage::disk($document->disk)->readStream($document->path);

        if ($stream === false) {
            throw new RuntimeException('The private document file is unavailable.');
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(config('documents.tika_timeout_seconds'))
                ->withBody(Utils::streamFor($stream), $document->mime_type)
                ->put(rtrim(config('documents.tika_url'), '/').'/tika/text')
                ->throw();
        } finally {
            fclose($stream);
        }

        $extractedText = trim($response->body());

        if ($extractedText === '') {
            $document->forceFill([
                'status' => 'failed',
                'failure_code' => 'no_extractable_text',
            ])->save();

            return;
        }

        Document::query()->whereKey($document->id)->where('status', 'extracting')->update([
            'status' => 'ready',
            'extracted_text' => $extractedText,
            'failure_code' => null,
        ]);
    }
}
