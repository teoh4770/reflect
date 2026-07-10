<?php

namespace Tests\Feature\Jobs;

use App\Agents\Challenger;
use App\Jobs\GenerateWeeklySummaryJob;
use App\Models\User;
use App\Models\WeeklySummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Mockery;
use Tests\TestCase;

class GenerateWeeklySummaryJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_summary_and_sends_notification()
    {
        $userWithToken = User::factory()->create([
            'fcm_token' => 'test-fcm-token-123',
            'identity_statement' => 'I am user 1',
        ]);

        $today = Carbon::parse('2023-10-15');
        Carbon::setTestNow($today);

        Challenger::fake([
            'Summary for user 1',
        ]);

        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock->shouldReceive('send')
            ->once()
            ->withArgs(function ($message) {
                return $message instanceof CloudMessage;
            });

        $job = new GenerateWeeklySummaryJob($userWithToken, $today);
        $job->handle(app(\App\Actions\CreateWeeklySummaryAction::class), $messagingMock);

        $this->assertDatabaseHas('weekly_summaries', [
            'user_id' => $userWithToken->id,
            'content' => 'Summary for user 1',
        ]);
        
        $this->assertEquals(1, WeeklySummary::count());
    }

    public function test_it_does_not_send_notification_if_no_fcm_token()
    {
        $userWithoutToken = User::factory()->create([
            'fcm_token' => null,
            'identity_statement' => 'I am user 2',
        ]);

        $today = Carbon::parse('2023-10-15');
        Carbon::setTestNow($today);

        Challenger::fake([
            'Summary for user 2',
        ]);

        $messagingMock = Mockery::mock(Messaging::class);
        $messagingMock->shouldNotReceive('send');

        $job = new GenerateWeeklySummaryJob($userWithoutToken, $today);
        $job->handle(app(\App\Actions\CreateWeeklySummaryAction::class), $messagingMock);

        $this->assertDatabaseHas('weekly_summaries', [
            'user_id' => $userWithoutToken->id,
            'content' => 'Summary for user 2',
        ]);
    }
}
