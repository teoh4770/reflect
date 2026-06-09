<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterruptTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_a_random_interrupt_prompt()
    {
        Prompt::query()->create(['ritual' => 'interrupt', 'body' => 'Test Prompt 1']);
        Prompt::query()->create(['ritual' => 'interrupt', 'body' => 'Test Prompt 2']);

        $response = $this->getJson('/api/interrupt');

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'body', 'ritual']);
    }

    public function test_can_store_a_text_entry_for_a_prompt()
    {
        $prompt = Prompt::query()->create(['ritual' => 'interrupt', 'body' => 'Test Prompt']);

        $response = $this->postJson('/api/entries', [
            'prompt_id' => $prompt->id,
            'body' => 'This is my response.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('entries', [
            'prompt_id' => $prompt->id,
            'body' => 'This is my response.',
        ]);
    }
}
