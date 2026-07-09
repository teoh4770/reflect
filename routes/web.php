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
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::inertia('/', 'Interrupt')->name('interrupt');

    Route::inertia('/armory', 'Armory')->name('armory');

    Route::get('/journal', [EntryController::class, 'index'])->name('journal');

    Route::get('/settings', function (\Illuminate\Http\Request $request) {
        return Inertia::render('Settings', [
            'identity_statement' => $request->user()->identity_statement,
            'schedule_slots' => ScheduleSlot::query()->orderBy('time')->get()
        ]);
    })->name('settings');

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/interrupt', [InterruptController::class, 'index'])->name('interrupt.index');

        Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');

        Route::get('/summary', [SummaryController::class, 'index'])->name('summary.index');
        Route::post('/summary', [SummaryController::class, 'store'])->name('summary.store');

        Route::post('/identity', [IdentityController::class, 'update'])->name('identity.update');

        Route::post('/transcribe-chunk', TranscriptionController::class)->name('transcribe-chunk');

        Route::post('/fcm-token', function () {
            $validated = request()->validate(['token' => 'required|string']);

            request()->user()->update(['fcm_token' => $validated['token']]);

            return response()->json(['success' => true]);
        })->name('fcm-token.update');

        Route::post('/test-notification', function () {
            if (request()->user()->email !== 'ck@gmail.com') {
                abort(HttpResponse::HTTP_FORBIDDEN);
            }

            if (!request()->user()->fcm_token) {
                return response()->json(['error' => 'No FCM token'], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            try {
                $messaging = Firebase::messaging();
                $message = CloudMessage::new()
                    ->withToken(request()->user()->fcm_token)
                    ->withNotification(FirebaseNotification::create(
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
            } catch (Exception $e) {
                Log::channel('stderr')->info($e->getMessage());
                return response()->json(['error' => $e->getMessage()], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        })->name('test-notification');
    });
});
