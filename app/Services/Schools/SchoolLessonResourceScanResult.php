<?php

declare(strict_types=1);

namespace App\Services\Schools;

final readonly class SchoolLessonResourceScanResult
{
    public function __construct(
        public SchoolLessonResourceScanVerdict $verdict,
        public string $scannerName,
        public ?string $signatureVersion = null,
    ) {}
}
