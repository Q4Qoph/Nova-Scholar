<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolLessonResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolLessonResource extends Model
{
    /** @use HasFactory<SchoolLessonResourceFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id', 'school_lesson_version_id', 'uploaded_by_user_id', 'display_name', 'storage_disk', 'storage_key',
        'media_type', 'byte_size', 'sha256', 'status', 'rights_basis', 'rights_reference', 'rights_attested_at',
        'validated_at', 'scanned_at', 'scanner_name', 'scanner_signature_version', 'scan_result_code', 'purge_after',
        'bytes_purged_at',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
            'rights_attested_at' => 'datetime',
            'validated_at' => 'datetime',
            'scanned_at' => 'datetime',
            'purge_after' => 'datetime',
            'bytes_purged_at' => 'datetime',
            'status' => SchoolLessonResourceStatus::class,
            'rights_basis' => SchoolLessonResourceRightsBasis::class,
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SchoolLessonVersion::class, 'school_lesson_version_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
