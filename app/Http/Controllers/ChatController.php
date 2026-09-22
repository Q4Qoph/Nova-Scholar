<?php

namespace App\Http\Controllers;

use App\Exceptions\EntitlementDenied;
use App\Http\Requests\StoreMessageRequest;
use App\Jobs\GenerateChatResponse;
use App\Models\Chat;
use App\Services\Usage\UsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): RedirectResponse
    {
        $chat = auth()->user()->chats()->latest()->first() ?? auth()->user()->chats()->create();

        return to_route('chats.show', $chat);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function draft()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create()
    {
        return to_route('chats.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Chat $chat)
    {
        Gate::authorize('view', $chat);

        return view('chats.show', [
            'chat' => $chat->load(['messages', 'documents']),
            'documents' => auth()->user()->documents()->where('status', 'ready')->latest()->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function store(StoreMessageRequest $request, Chat $chat, UsageService $usage): RedirectResponse
    {
        Gate::authorize('update', $chat);
        $documentIds = collect($request->validated('documents', []))->unique()->values();
        $documents = auth()->user()->documents()->whereIn('id', $documentIds)->where('status', 'ready')->get();
        if ($documents->count() !== $documentIds->count()) {
            throw ValidationException::withMessages(['documents' => 'Select only your ready documents.']);
        }
        $chat->documents()->sync($documents->modelKeys());
        $requestKey = (string) Str::uuid();
        try {
            $usage->reserve($request->user(), 'ai_chat', 1, $requestKey);
        } catch (EntitlementDenied $exception) {
            throw ValidationException::withMessages(['content' => $exception->getMessage()]);
        }
        $message = $chat->messages()->create(['role' => 'user', 'content' => $request->string('content')->trim(), 'status' => 'pending', 'request_key' => $requestKey]);
        GenerateChatResponse::dispatch($message);

        return to_route('chats.show', $chat);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
