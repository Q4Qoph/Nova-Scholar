<?php

declare(strict_types=1);

namespace App\Services\Schools;

enum SchoolLessonResourceScanVerdict: string
{
    case Clean = 'clean';
    case Infected = 'infected';
    case Unavailable = 'unavailable';
}
