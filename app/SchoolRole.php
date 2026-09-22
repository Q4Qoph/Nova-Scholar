<?php

declare(strict_types=1);

namespace App;

enum SchoolRole: string
{
    case SchoolAdmin = 'school_admin';
    case Teacher = 'teacher';
    case Bursar = 'bursar';
    case Guardian = 'guardian';
    case Learner = 'learner';

    public function isStaffRole(): bool
    {
        return match ($this) {
            self::SchoolAdmin, self::Teacher, self::Bursar => true,
            self::Guardian, self::Learner => false,
        };
    }
}
