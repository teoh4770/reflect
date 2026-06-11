<?php

namespace Tests\Feature;

use App\Agents\Challenger;
use App\Models\User;
use App\Models\WeeklySummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class WeeklySummaryTest extends TestCase
{
    use RefreshDatabase;

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
}
