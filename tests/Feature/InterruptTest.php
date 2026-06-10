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
}
