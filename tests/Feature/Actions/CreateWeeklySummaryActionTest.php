<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateWeeklySummaryAction;
use App\Agents\Challenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateWeeklySummaryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_weekly_summary()
    {
        $user = User::factory()->create([
            'identity_statement' => 'I am a tester.',
        ]);

        $today = Carbon::parse('2023-10-15'); // A Sunday

        Challenger::fake(['You did a great job testing!']);

        $action = new CreateWeeklySummaryAction();
        $summary = $action->execute($today, $user);

        $this->assertEquals($today->copy()->startOfWeek()->toDateString(), Carbon::parse($summary->week_start)->toDateString());
        $this->assertEquals($today->copy()->endOfWeek()->toDateString(), Carbon::parse($summary->week_end)->toDateString());

        $this->assertDatabaseHas('weekly_summaries', [
            'id' => $summary->id,
            'user_id' => $user->id,
            'identity_snapshot' => 'I am a tester.',
            'content' => 'You did a great job testing!',
        ]);

        Challenger::assertPrompted(function ($prompt) use ($today) {
            return str_contains($prompt->prompt, 'Summarize my week from ' . $today->copy()->startOfWeek()->toDateString() . ' to ' . $today->copy()->endOfWeek()->toDateString());
        });
    }
}
