<?php

namespace Tests\Feature\Agents;

use App\Agents\Challenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class ChallengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_includes_the_users_identity_statement_in_instructions()
    {
        $user = User::factory()->create([
            'identity_statement' => 'I am a disciplined athlete.'
        ]);

        Challenger::fake(['Brutal awareness provided.']);

        (new Challenger($user))->prompt('I skipped my morning run.');

        Challenger::assertPrompted(function (AgentPrompt $prompt) use ($user) {
            return str_contains($prompt->agent->instructions(), $user->identity_statement);
        });
    }
}
