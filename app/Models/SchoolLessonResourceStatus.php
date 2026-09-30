<?php

declare(strict_types=1);

namespace App\Models;

enum SchoolLessonResourceStatus: string
{
    case Quarantined = 'quarantined';
    case Validating = 'validating';
    case ScanPending = 'scan_pending';
    case Clean = 'clean';
    case Rejected = 'rejected';
    case Purged = 'purged';
}
