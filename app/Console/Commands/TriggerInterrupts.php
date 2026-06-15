<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\ScheduleSlot;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Laravel\Firebase\Facades\Firebase;

class TriggerInterrupts extends Command
{
    protected $signature = 'app:trigger-interrupts';
    protected $description = 'Trigger an interrupt event if the current time matches a schedule slot';

    public function handle()
    {
        $currentTime = now()->format('H:i');

        // Match the current hour and minute
        $slots = ScheduleSlot::all()->filter(function ($slot) use ($currentTime) {
            return Carbon::parse($slot->time)->format('H:i') === $currentTime;
        });

        if ($slots->isNotEmpty()) {
            // Send FCM Push Notifications to all devices
            $users = User::whereNotNull('fcm_token')->get();
            if ($users->isNotEmpty()) {
                try {
                    $messaging = Firebase::messaging();
                    
                    // Pluck all tokens
                    $tokens = $users->pluck('fcm_token')->toArray();
                    
                    // Create the base message once
                    $message = CloudMessage::new()
                        ->withNotification(Notification::create(
                            'Time to Reflect',
                            "Your {$currentTime} interrupt is ready. Take a moment for brutal awareness."
                        ))
                        ->withWebPushConfig(WebPushConfig::fromArray([
                            'notification' => [
                                'icon' => '/apple-touch-icon.png'
                            ]
                        ]));

                    // Firebase limits multicast sending to 500 tokens per request
                    $chunks = array_chunk($tokens, 500);

                    foreach ($chunks as $chunk) {
                        $messaging->sendMulticast($message, $chunk);
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to send FCM notifications: " . $e->getMessage());
                }
            }

            $this->info("Interrupt triggered at {$currentTime}");
        } else {
            $this->info("No interrupt scheduled for {$currentTime}");
        }
    }
}
