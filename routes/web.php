<?php

use App\Http\Controllers\IdentityController;
use App\Http\Controllers\InterruptController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TranscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Interrupt');
    })->name('interrupt');

    Route::get('/armory', function () {
        return Inertia::render('Armory');
    })->name('armory');

    Route::get('/journal', [\App\Http\Controllers\EntryController::class, 'index'])->name('journal');

    Route::get('/settings', function (\Illuminate\Http\Request $request) {
        return Inertia::render('Settings', [
            'identity_statement' => $request->user()->identity_statement
        ]);
    })->name('settings');

    Route::get('/api/interrupt', [InterruptController::class, 'index']);
    Route::post('/api/entries', [InterruptController::class, 'store']);
    Route::get('/api/summary', [SummaryController::class, 'index']);
    Route::post('/api/summary', [SummaryController::class, 'store']);
    Route::post('/api/identity', [IdentityController::class, 'update']);
    Route::post('/api/transcribe-chunk', TranscriptionController::class);
});
