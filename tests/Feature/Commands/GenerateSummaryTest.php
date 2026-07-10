<?php

namespace Tests\Feature\Commands;

use App\Jobs\GenerateWeeklySummaryJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_jobs_for_all_users()
    {
        // 1. Arrange
        $user1 = User::factory()->create([
            'fcm_token' => null,
            'identity_statement' => 'I am user 1',
        ]);

        $user2 = User::factory()->create([
            'fcm_token' => 'test-fcm-token-123',
            'identity_statement' => 'I am user 2',
        ]);

        $today = Carbon::parse('2023-10-15');
        Carbon::setTestNow($today);

        Queue::fake();

        // 2. Act
        $this->artisan('summary:generate')->assertExitCode(0);

        // 3. Assert
        Queue::assertPushed(GenerateWeeklySummaryJob::class, 2);

        Queue::assertPushed(GenerateWeeklySummaryJob::class, function ($job) use ($user1, $today) {
            return $job->user->id === $user1->id && $job->date->equalTo($today);
        });

        Queue::assertPushed(GenerateWeeklySummaryJob::class, function ($job) use ($user2, $today) {
            return $job->user->id === $user2->id && $job->date->equalTo($today);
        });
    }
}
