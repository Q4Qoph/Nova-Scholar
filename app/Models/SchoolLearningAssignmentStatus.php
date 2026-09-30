<?php

declare(strict_types=1);

namespace App\Models;

enum SchoolLearningAssignmentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Withdrawn = 'withdrawn';
}
