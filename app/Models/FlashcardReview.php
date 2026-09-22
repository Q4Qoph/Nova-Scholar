<?php

namespace App\Models;

use Database\Factories\FlashcardReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardReview extends Model
{
    /** @use HasFactory<FlashcardReviewFactory> */
    use HasFactory;

    protected $fillable = ['flashcard_id', 'user_id', 'outcome', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
