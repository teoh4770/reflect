<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\ScheduleSlot;
use App\Events\InterruptTriggered;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
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
            event(new InterruptTriggered());

            // Send FCM Push Notifications to offline devices
            $users = User::whereNotNull('fcm_token')->get();
            if ($users->isNotEmpty()) {
                try {
                    $messaging = Firebase::messaging();
                    $messages = [];

                    foreach ($users as $user) {
                        $messages[] = CloudMessage::new()
                            ->withToken($user->fcm_token)
                            ->withNotification(Notification::create(
                                'Time to Reflect',
                                "Your {$currentTime} interrupt is ready. Take a moment for brutal awareness."
                            ));
                    }

                    if (!empty($messages)) {
                        $messaging->sendAll($messages);
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
