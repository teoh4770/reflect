<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\ScheduleSlot;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InterruptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed some slots
        ScheduleSlot::create(['time' => '11:00:00']);
        ScheduleSlot::create(['time' => '13:30:00']);
        ScheduleSlot::create(['time' => '17:00:00']);

        // Seed some prompts
        Prompt::create(['ritual' => 'interrupt', 'body' => 'Prompt 1']);
        Prompt::create(['ritual' => 'interrupt', 'body' => 'Prompt 2']);
        Prompt::create(['ritual' => 'interrupt', 'body' => 'Prompt 3']);
    }

    public function test_it_is_locked_before_the_first_slot_of_the_day()
    {
        Carbon::setTestNow('2026-06-10 09:00:00');

        $response = $this->getJson('/api/interrupt');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'locked',
                'next_slot_at' => '2026-06-10 11:00:00'
            ]);
    }

    public function test_it_returns_a_prompt_during_an_active_slot_window()
    {
        Carbon::setTestNow('2026-06-10 11:30:00');

        $response = $this->getJson('/api/interrupt');

        $currentPrompt = $response->json('prompt');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'active',
                'slot' => ['time' => '11:00:00']
            ])
            ->assertJsonStructure(['prompt' => ['id', 'body']]);

        // Make another call to interrupt api
        $response = $this->getJson('/api/interrupt');

        $nextPrompt = $response->json('prompt');

        $this->assertEquals($nextPrompt['id'], $currentPrompt['id']);
    }

    // Todo: reset the active state for interrupt at midnight

    public function test_it_returns_a_prompt_that_already_active_during_an_active_slot_window()
    {
        Carbon::setTestNow('2026-06-10 11:30:00');

        $response = $this->getJson('/api/interrupt');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'active',
                'slot' => ['time' => '11:00:00']
            ])
            ->assertJsonStructure(['prompt' => ['id', 'body']]);
    }

    public function test_it_locks_once_an_entry_is_submitted_for_the_current_slot()
    {
        Carbon::setTestNow('2026-06-10 11:30:00');
        $slot = ScheduleSlot::where('time', '11:00:00')->first();
        $prompt = Prompt::first();

        // Submit an entry
        $this->postJson('/api/entries', [
            'prompt_id' => $prompt->id,
            'slot_id' => $slot->id,
            'body' => 'My response'
        ]);

        // Try to fetch again
        $response = $this->getJson('/api/interrupt');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'locked',
                'next_slot_at' => '2026-06-10 13:30:00'
            ]);
    }

    public function test_it_resets_availability_at_midnight_for_the_next_day()
    {
        Carbon::setTestNow('2026-06-10 11:30:00');
        $slot = ScheduleSlot::where('time', '11:00:00')->first();
        $prompt = Prompt::first();

        // Answer for today
        $this->postJson('/api/entries', [
            'prompt_id' => $prompt->id,
            'slot_id' => $slot->id,
            'body' => 'Answer today'
        ]);

        // Fast forward to tomorrow at same time
        Carbon::setTestNow('2026-06-11 11:30:00');

        $response = $this->getJson('/api/interrupt');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'active',
                'slot' => ['time' => '11:00:00']
            ]);
    }

    public function test_it_does_not_repeat_prompts_within_the_same_day()
    {
        Carbon::setTestNow('2026-06-10 11:30:00');
        $slot1 = ScheduleSlot::where('time', '11:00:00')->first();

        // Get first prompt
        $res1 = $this->getJson('/api/interrupt');
        $prompt1Id = $res1->json('prompt.id');

        // Submit entry for slot 1
        $this->postJson('/api/entries', [
            'prompt_id' => $prompt1Id,
            'slot_id' => $slot1->id,
            'body' => 'Done slot 1'
        ]);

        // Move to next slot
        Carbon::setTestNow('2026-06-10 14:00:00');
        $res2 = $this->getJson('/api/interrupt');

        $this->assertNotEquals($prompt1Id, $res2->json('prompt.id'));
    }
}
