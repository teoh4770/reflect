<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EntryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_journal_page_with_entries_grouped_by_week(): void
    {
        $user = User::factory()->create();

        $entry1 = Entry::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->startOfWeek(),
        ]);

        $entry2 = Entry::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->startOfWeek()->subWeek(),
        ]);

        $response = $this->actingAs($user)->get('/journal');

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Journal')
            ->has('entriesByWeek')
        );
        $response->assertSee($entry1->body);
        $response->assertSee($entry2->body);
    }
}
