<?php

use App\Http\Controllers\IdentityController;
use App\Http\Controllers\InterruptController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TranscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/interrupt', function () {
        return Inertia::render('Interrupt');
    })->name('interrupt');

    Route::get('/armory', function () {
        return Inertia::render('Armory');
    })->name('armory');

    Route::get('/api/interrupt', [InterruptController::class, 'index']);
    Route::post('/api/entries', [InterruptController::class, 'store']);
    Route::get('/api/summary', [SummaryController::class, 'index']);
    Route::post('/api/summary', [SummaryController::class, 'store']);
    Route::post('/api/identity', [IdentityController::class, 'update']);
    Route::post('/api/transcribe-chunk', TranscriptionController::class);
});
