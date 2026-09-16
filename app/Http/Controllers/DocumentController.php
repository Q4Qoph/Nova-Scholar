<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocument;
use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('documents.index', ['documents' => auth()->user()->documents()->latest()->get()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $file = $request->file('document');
        $document = new Document([
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'disk' => 'local',
            'path' => $file->store('documents'),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
        $document->user()->associate($request->user());
        $document->save();
        ProcessDocument::dispatch($document)->afterCommit();

        return to_route('documents.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return to_route('documents.index');
    }
}
