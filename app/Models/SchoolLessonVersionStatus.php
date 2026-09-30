<?php

declare(strict_types=1);

namespace App\Models;

enum SchoolLessonVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Superseded = 'superseded';
    case Withdrawn = 'withdrawn';
}
