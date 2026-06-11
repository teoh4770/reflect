<?php

namespace Tests\Feature\Tools;

use App\Models\Entry;
use App\Models\Prompt;
use App\Models\User;
use App\Tools\FetchEntries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class FetchEntriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_entries_for_the_given_user_and_date_range()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prompt = Prompt::factory()->create();

        // Entry for target user (in range)
        $entry1 = new Entry([
            'user_id' => $user->id,
            'prompt_id' => $prompt->id,
            'body' => 'I worked out today.',
        ]);
        $entry1->created_at = now()->subDays(2);
        $entry1->save();

        // Entry for target user (out of range)
        $entry2 = new Entry([
            'user_id' => $user->id,
            'prompt_id' => $prompt->id,
            'body' => 'I failed 10 days ago.',
        ]);
        $entry2->created_at = now()->subDays(10);
        $entry2->save();

        // Entry for another user
        $entry3 = new Entry([
            'user_id' => $otherUser->id,
            'prompt_id' => $prompt->id,
            'body' => 'Other user entry.',
        ]);
        $entry3->created_at = now()->subDays(2);
        $entry3->save();

        $tool = new FetchEntries($user);
        $request = new Request([
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $result = $tool->handle($request);

        $this->assertStringContainsString('I worked out today.', $result);
        $this->assertStringNotContainsString('I failed 10 days ago.', $result);
        $this->assertStringNotContainsString('Other user entry.', $result);
    }
}
