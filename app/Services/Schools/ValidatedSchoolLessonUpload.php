<?php

declare(strict_types=1);

namespace App\Services\Schools;

final readonly class ValidatedSchoolLessonUpload
{
    public function __construct(
        public string $path,
        public string $extension,
        public string $mediaType,
        public bool $temporaryPath,
    ) {}
}
