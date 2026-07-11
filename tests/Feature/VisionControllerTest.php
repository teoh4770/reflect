<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Prompt;
use App\Models\Entry;
use Inertia\Testing\AssertableInertia as Assert;

class VisionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_vision_page_with_prompts(): void
    {
        $user = User::factory()->create();

        Prompt::factory()->create(['ritual' => 'pain']);
        Prompt::factory()->create(['ritual' => 'anti-vision']);
        Prompt::factory()->create(['ritual' => 'vision']);
        Prompt::factory()->create(['ritual' => 'interrupt']);

        $response = $this->actingAs($user)->get('/visions');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Vision')
            ->has('prompts', 3)
        );
    }

    public function test_store_creates_new_entry(): void
    {
        $user = User::factory()->create();
        $prompt = Prompt::factory()->create(['ritual' => 'pain']);

        $response = $this->actingAs($user)->post('/visions', [
            'prompt_id' => $prompt->id,
            'body' => 'This is a test pain entry.',
        ]);

        $response->assertRedirect('/visions');
        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'prompt_id' => $prompt->id,
            'body' => 'This is a test pain entry.',
        ]);
    }

    public function test_update_modifies_existing_entry(): void
    {
        $user = User::factory()->create();
        $prompt = Prompt::factory()->create(['ritual' => 'anti-vision']);
        
        $entry = Entry::factory()->create([
            'user_id' => $user->id,
            'prompt_id' => $prompt->id,
            'body' => 'Old entry body.',
        ]);

        $response = $this->actingAs($user)->post("/visions/{$entry->id}", [
            'body' => 'Updated entry body.',
        ]);

        $response->assertRedirect('/visions');
        $this->assertDatabaseHas('entries', [
            'id' => $entry->id,
            'body' => 'Updated entry body.',
        ]);
    }
}
