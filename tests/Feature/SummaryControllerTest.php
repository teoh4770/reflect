<?php

namespace Tests\Feature;

use App\Agents\Challenger;
use App\Models\User;
use App\Models\WeeklySummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class SummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_fetch_summaries_and_status()
    {
        // Mock a regular day
        Date::setTestNow(now()->startOfWeek()->addDays(3)); // Thursday

        $user = User::factory()->create([
            'identity_statement' => 'I am testing this.'
        ]);
        $this->actingAs($user);

        // Create an older summary
        $summary = WeeklySummary::create([
            'user_id' => $user->id,
            'week_start' => now()->subWeeks(1)->startOfWeek()->toDateString(),
            'week_end' => now()->subWeeks(1)->endOfWeek()->toDateString(),
            'identity_snapshot' => 'I am testing this.',
            'content' => 'Old summary content',
        ]);

        $response = $this->getJson('/api/summary');

        $response->assertStatus(200);
        $response->assertJson([
            'summaries' => [
                [
                    'id' => $summary->id,
                ]
            ],
            'is_sunday' => false,
            'identity_statement' => 'I am testing this.',
            'already_finished' => false,
        ]);
    }

    public function test_index_shows_already_finished_true_if_summary_exists_for_current_week()
    {
        Date::setTestNow(now()->startOfWeek()->addDays(6)); // Sunday

        $user = User::factory()->create();
        $this->actingAs($user);

        WeeklySummary::create([
            'user_id' => $user->id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'week_end' => now()->endOfWeek()->toDateString(),
            'identity_snapshot' => 'Test',
            'content' => 'Test',
        ]);

        $response = $this->getJson('/api/summary');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'already_finished' => true,
        ]);
    }

    public function test_it_generates_and_persists_a_weekly_summary_on_sunday()
    {
        // Mock Sunday
        Date::setTestNow(now()->startOfWeek()->addDays(6)); // Sunday

        $user = User::factory()->create([
            'identity_statement' => 'I am a disciplined athlete.'
        ]);
        $this->actingAs($user);

        Challenger::fake(['Your week was a direct contradiction of your identity.']);

        $response = $this->postJson('/api/summary');

        $response->assertStatus(201);
        $this->assertDatabaseHas('weekly_summaries', [
            'user_id' => $user->id,
            'identity_snapshot' => 'I am a disciplined athlete.',
            'content' => 'Your week was a direct contradiction of your identity.',
        ]);

        Challenger::assertPrompted(function ($prompt) {
            return str_contains($prompt->prompt, 'Summarize my week');
        });
    }

    public function test_it_cannot_generate_summary_if_not_sunday()
    {
        // Mock Monday
        Date::setTestNow(now()->startOfWeek());

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/summary');

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'The "Finish the Week" ritual is only available on Sundays.');
    }

    public function test_it_prevents_generating_summary_if_already_finished_this_week()
    {
        // Mock Sunday
        Date::setTestNow(now()->startOfWeek()->addDays(6)); // Sunday

        $user = User::factory()->create();
        $this->actingAs($user);

        WeeklySummary::create([
            'user_id' => $user->id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'week_end' => now()->endOfWeek()->toDateString(),
            'identity_snapshot' => 'Test',
            'content' => 'Test',
        ]);

        $response = $this->postJson('/api/summary');

        $response->assertStatus(409);
        $response->assertJsonPath('error', 'You have already finished this week.');
    }
}
