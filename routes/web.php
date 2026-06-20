<?php

use App\Http\Controllers\EntryController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\InterruptController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TranscriptionController;
use App\Models\ScheduleSlot;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AuthController;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Laravel\Firebase\Facades\Firebase;

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

    Route::get('/journal', [EntryController::class, 'index'])->name('journal');

    Route::get('/settings', function (\Illuminate\Http\Request $request) {
        return Inertia::render('Settings', [
            'identity_statement' => $request->user()->identity_statement,
            'schedule_slots' => ScheduleSlot::query()->orderBy('time')->get()
        ]);
    })->name('settings');

    Route::get('/api/interrupt', [InterruptController::class, 'index']);
    Route::post('/api/entries', [EntryController::class, 'store']);
    Route::get('/api/summary', [SummaryController::class, 'index']);
    Route::post('/api/summary', [SummaryController::class, 'store']);
    Route::post('/api/identity', [IdentityController::class, 'update']);
    Route::post('/api/transcribe-chunk', TranscriptionController::class);
    Route::post('/api/fcm-token', function (\Illuminate\Http\Request $request) {
        $request->validate(['token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->token]);
        return response()->json(['success' => true]);
    });

    Route::post('/api/test-notification', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if ($user->email !== 'ck@gmail.com') {
            abort(403);
        }
        if (!$user->fcm_token) {
            return response()->json(['error' => 'No FCM token'], 400);
        }

        try {
            $messaging = Firebase::messaging();
            $message = CloudMessage::new()
                ->withToken($user->fcm_token)
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create(
                    'Test Notification',
                    'This is a test interrupt ping from Reflect.'
                ))
                ->withWebPushConfig(WebPushConfig::fromArray([
                    'notification' => [
                        'icon' => '/apple-touch-icon.png'
                    ],
                    'fcm_options' => [
                        'link' => config('app.url')
                    ]
                ]));

            $messaging->send($message);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::channel('stderr')->info($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});
