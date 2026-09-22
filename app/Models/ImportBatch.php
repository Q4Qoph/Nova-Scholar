<?php

namespace App\Models;

use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'uploaded_by_user_id', 'source_filename', 'source_checksum', 'status', 'total_rows', 'valid_rows', 'invalid_rows', 'committed_at'];

    protected function casts(): array
    {
        return ['committed_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }
}
