<?php

namespace App\Models;

use Database\Factories\QuizAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    /** @use HasFactory<QuizAnswerFactory> */
    use HasFactory;

    protected $fillable = ['quiz_attempt_id', 'question_id', 'response', 'awarded_points', 'feedback'];

    protected function casts(): array
    {
        return ['awarded_points' => 'integer'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
