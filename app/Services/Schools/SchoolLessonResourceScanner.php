<?php

declare(strict_types=1);

namespace App\Services\Schools;

interface SchoolLessonResourceScanner
{
    public function scan(string $absolutePath): SchoolLessonResourceScanResult;
}
