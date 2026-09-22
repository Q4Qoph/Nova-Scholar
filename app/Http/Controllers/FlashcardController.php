<?php

namespace App\Http\Controllers;

use App\Exceptions\EntitlementDenied;
use App\Http\Requests\GenerateFlashcardsRequest;
use App\Http\Requests\StoreFlashcardReviewRequest;
use App\Jobs\GenerateFlashcards;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Services\Usage\UsageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FlashcardController extends Controller
{
    public function index(): View
    {
        return view('flashcards.index', ['decks' => auth()->user()->flashcardDecks()->latest()->get()]);
    }

    public function show(FlashcardDeck $flashcardDeck): View
    {
        Gate::authorize('view', $flashcardDeck);

        return view('flashcards.show', ['deck' => $flashcardDeck->load('flashcards')]);
    }

    public function review(StoreFlashcardReviewRequest $request, FlashcardDeck $flashcardDeck, Flashcard $flashcard): RedirectResponse
    {
        Gate::authorize('view', $flashcardDeck);
        abort_unless($flashcard->flashcard_deck_id === $flashcardDeck->id, 404);
        $flashcard->reviews()->create(['user_id' => auth()->id(), 'outcome' => $request->string('outcome')->toString(), 'reviewed_at' => now()]);

        return to_route('flashcards.show', $flashcardDeck);
    }

    public function store(GenerateFlashcardsRequest $request, UsageService $usage): RedirectResponse
    {
        $topic = $request->string('topic')->toString();
        $requestKey = (string) Str::uuid();
        try {
            $usage->reserve($request->user(), 'flashcard_generation', 1, $requestKey);
        } catch (EntitlementDenied $exception) {
            throw ValidationException::withMessages(['topic' => $exception->getMessage()]);
        }
        $deck = $request->user()->flashcardDecks()->create([
            'title' => $topic,
            'status' => 'pending',
            'request_key' => $requestKey,
        ]);
        GenerateFlashcards::dispatch($deck, $topic, $request->integer('count'));

        return to_route('flashcards.show', $deck);
    }
}
