<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolLearningReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolLearningReview extends Model
{
    /** @use HasFactory<SchoolLearningReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id', 'school_learning_submission_id', 'reviewed_by_user_id',
        'feedback', 'score', 'maximum_score', 'released_by_user_id', 'released_at',
    ];

    protected function casts(): array
    {
        return ['score' => 'integer', 'maximum_score' => 'integer', 'released_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SchoolLearningSubmission::class, 'school_learning_submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }
}
