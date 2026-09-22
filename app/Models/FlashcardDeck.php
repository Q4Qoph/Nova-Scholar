<?php

namespace App\Models;

use Database\Factories\FlashcardDeckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashcardDeck extends Model
{
    /** @use HasFactory<FlashcardDeckFactory> */
    use HasFactory;

    protected $fillable = ['title', 'status', 'request_key', 'failure_code'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }
}
