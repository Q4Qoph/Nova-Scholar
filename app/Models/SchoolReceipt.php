<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolReceipt extends Model
{
    protected $fillable = [
        'school_id', 'verified_by_user_id', 'source', 'source_reference', 'submission_key',
        'currency', 'amount_minor', 'received_on', 'verification_note', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'received_on' => 'date', 'verified_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FeeReceiptAllocation::class);
    }
}
