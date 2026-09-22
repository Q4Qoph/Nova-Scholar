<?php

namespace App\Models;

use Database\Factories\FlashcardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flashcard extends Model
{
    /** @use HasFactory<FlashcardFactory> */
    use HasFactory;

    protected $fillable = ['front', 'back'];

    public function deck(): BelongsTo
    {
        return $this->belongsTo(FlashcardDeck::class, 'flashcard_deck_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FlashcardReview::class);
    }
}
