<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ModelOptionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ImageGenerationController;


Route::get('/', function () {
    return redirect()->route('chats.index');
});

Route::middleware(['auth:web'])->group(function () {
    // Chats
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::post('/chats', [ChatController::class, 'store'])->name('chats.store');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::put('/chats/{chat}', [ChatController::class, 'update'])->name('chats.update');
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy'])->name('chats.destroy');

    // Messages
    Route::post('/chats/{chat}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::put('/chats/{chat}/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('/chats/{chat}/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('/chats/{chat}/regenerate', [MessageController::class, 'regenerate'])->name('messages.regenerate');

    // Model Options

    Route::post('/chats/{chat}/generate-image', 'ChatController@generateImage')->name('chats.generate-image');
    Route::get('/model-options', [App\Http\Controllers\ModelOptionController::class, 'index']);
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
