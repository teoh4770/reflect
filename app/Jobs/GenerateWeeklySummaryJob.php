<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Actions\CreateWeeklySummaryAction;
use App\Models\User;
use Exception;
use Carbon\CarbonInterface;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Log;

class GenerateWeeklySummaryJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public CarbonInterface $date
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CreateWeeklySummaryAction $createWeeklySummaryAction, Messaging $messaging): void
    {
        try {
            $weeklySummary = $createWeeklySummaryAction->execute($this->date->copy(), $this->user);

            if ($weeklySummary->exists && filled($this->user->fcm_token)) {
                $this->sendNotification($this->user, $this->date->copy()->toDateString(), $messaging);
            }
        } catch (Exception $e) {
            Log::error("Failed to generate summary for User ID {$this->user->id}: " . $e->getMessage());
        }
    }

    private function sendNotification(User $user, string $currentTime, Messaging $messaging): void
    {
        try {
            $notification = Notification::create(
                'Here is your weekly summary',
                "Your weekly summary for $currentTime is here."
            );

            $webPushConfig = WebPushConfig::fromArray([
                'notification' => [
                    'icon' => '/apple-touch-icon.png'
                ],
                'fcm_options' => [
                    'link' => route('armory')
                ]
            ]);

            $message = CloudMessage::new()
                ->withToken($user->fcm_token)
                ->withNotification($notification)
                ->withWebPushConfig($webPushConfig);

            $messaging->send($message);
        } catch (MessagingException|FirebaseException $e) {
            Log::error("Firebase Messaging service has issue for User ID {$user->id}: " . $e->getMessage());
        } catch (Exception $e) {
            Log::error("Failed to send FCM notifications for User ID {$user->id}: " . $e->getMessage());
        }
    }
}
