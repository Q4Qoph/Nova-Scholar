<?php

namespace App\Models;

use App\SchoolRole;
use Database\Factories\SchoolRoleAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolRoleAssignment extends Model
{
    /** @use HasFactory<SchoolRoleAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['school_membership_id', 'role'];

    protected function casts(): array
    {
        return ['role' => SchoolRole::class];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(SchoolMembership::class, 'school_membership_id');
    }
}
