<?php

declare(strict_types=1);

namespace App\Models;

enum SchoolLearningSubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
}
