<?php

declare(strict_types=1);

namespace App\Models;

enum SchoolLessonResourceRightsBasis: string
{
    case EducatorCreated = 'educator_created';
    case SchoolOwned = 'school_owned';
    case Licensed = 'licensed';
    case PermissionGranted = 'permission_granted';
}
