<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Jobs\GenerateChatResponse;
use App\Models\Chat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

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

        return view('chats.show', ['chat' => $chat->load('messages')]);
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
    public function store(StoreMessageRequest $request, Chat $chat): RedirectResponse
    {
        Gate::authorize('update', $chat);
        $message = $chat->messages()->create(['role' => 'user', 'content' => $request->string('content')->trim(), 'status' => 'pending', 'request_key' => (string) Str::uuid()]);
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
