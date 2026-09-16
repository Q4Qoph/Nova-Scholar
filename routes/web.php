<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/subscription', [SubscriptionController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('subscription.index');

Route::resource('documents', DocumentController::class)
    ->only(['index', 'store', 'destroy'])
    ->middleware(['auth', 'verified']);

Route::get('/chat', [ChatController::class, 'index'])->middleware(['auth', 'verified'])->name('chats.index');
Route::get('/chat/{chat}', [ChatController::class, 'show'])->middleware(['auth', 'verified'])->name('chats.show');
Route::post('/chat/{chat}/messages', [ChatController::class, 'store'])->middleware(['auth', 'verified'])->name('chats.messages.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile/photo', ProfilePhotoController::class)->name('profile.photo');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
