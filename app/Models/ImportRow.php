<?php

namespace App\Models;

use Database\Factories\ImportRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    /** @use HasFactory<ImportRowFactory> */
    use HasFactory;

    protected $fillable = ['import_batch_id', 'row_number', 'payload', 'validation_errors', 'status', 'committed_enrolment_id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'validation_errors' => 'array'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function committedEnrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class, 'committed_enrolment_id');
    }
}
