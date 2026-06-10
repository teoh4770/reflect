<?php

use App\Http\Controllers\InterruptController;
use App\Http\Controllers\TranscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/interrupt', function () {
    return Inertia::render('Interrupt');
})->name('interrupt');

Route::get('/api/interrupt', [InterruptController::class, 'index']);
Route::post('/api/entries', [InterruptController::class, 'store']);
Route::post('/api/transcribe-chunk', TranscriptionController::class);
