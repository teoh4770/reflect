<?php

namespace Tests\Unit;

use App\Models\Entry;
use App\Models\Prompt;
use App\Models\User;
use Database\Seeders\InterruptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_can_load_soft_deleted_prompt(): void
    {
        $user = User::factory()->create();
        $prompt = Prompt::factory()->create();

        $entry = Entry::factory()->create([
            'user_id' => $user->id,
            'prompt_id' => $prompt->id,
        ]);

        // Soft delete the prompt
        $prompt->delete();

        $this->assertSoftDeleted($prompt);

        // Fetch the entry fresh and load prompt
        $fetchedEntry = Entry::with('prompt')->find($entry->id);

        $this->assertNotNull($fetchedEntry->prompt);
        $this->assertEquals($prompt->id, $fetchedEntry->prompt->id);
    }

    public function test_prompt_seeder_does_not_duplicate_soft_deleted_prompts(): void
    {
        // Run the seeder to create prompts
        $this->seed(InterruptSeeder::class);

        // Get the first prompt and soft delete it
        $prompt = Prompt::where('ritual', 'interrupt')->first();
        $promptBody = $prompt->body;
        $promptId = $prompt->id;

        $prompt->delete();
        $this->assertSoftDeleted($prompt);

        // Run the seeder again
        $this->seed(InterruptSeeder::class);

        // Check that a new prompt was not created with the same body
        $count = Prompt::withTrashed()->where('body', $promptBody)->count();
        $this->assertEquals(1, $count);

        // Ensure the ID is still the same (not recreated)
        $this->assertEquals($promptId, Prompt::withTrashed()->where('body', $promptBody)->first()->id);
    }
}
