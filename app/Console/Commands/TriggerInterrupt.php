<?php

namespace App\Console\Commands;

use App\Models\User;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\ScheduleSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Laravel\Firebase\Facades\Firebase;

#[Signature('interrupt:trigger')]
#[Description('Trigger an interrupt event if the current time matches a schedule slot')]
class TriggerInterrupt extends Command
{
    const int FIREBASE_MULTICAST_LIMIT = 500;

    public function handle(): void
    {
        $currentTime = now()->format('H:i:s');
        $timeSlot = ScheduleSlot::query()
            ->whereTime('time', $currentTime)
            ->first();

        if (is_null($timeSlot)) {
            Log::info("No interrupt scheduled for $currentTime");
            return;
        }

        $usersWithToken = User::query()
            ->whereNotNull('fcm_token')
            ->get();

        if ($usersWithToken->isNotEmpty()) {
            $this->sendNotification($usersWithToken, $currentTime);
        }

        Log::info("Interrupt triggered at $currentTime");
    }

    private function sendNotification(Collection $users, string $currentTime): void
    {
        try {
            $tokens = $users->pluck('fcm_token')->toArray();

            $messaging = Firebase::messaging();
            $notification = Notification::create(
                'Time to Reflect',
                "Your $currentTime interrupt is ready. Take a moment for brutal awareness."
            );
            $webPushConfig = WebPushConfig::fromArray([
                'notification' => [
                    'icon' => '/apple-touch-icon.png'
                ],
                'fcm_options' => [
                    'link' => config('app.url')
                ]
            ]);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withWebPushConfig($webPushConfig);

            $chunks = array_chunk($tokens, self::FIREBASE_MULTICAST_LIMIT);
            foreach ($chunks as $chunk) {
                $messaging->sendMulticast($message, $chunk);
            }
        } catch (MessagingException|FirebaseException $e) {
            $this->error('Firebase Messaging service has issue: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->error('Failed to send FCM notifications: ' . $e->getMessage());
        }
    }
}
