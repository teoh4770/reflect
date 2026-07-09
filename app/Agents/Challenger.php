<?php

namespace App\Agents;

use App\Models\User;
use App\Tools\FetchEntries;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class Challenger implements Agent, Conversational, HasTools
{
    use Promptable;

    public function __construct(public ?User $user = null)
    {
    }

    public function instructions(): Stringable|string
    {
        $instructions = <<<'PROMPT'
        You are the "Challenger AI", the Dungeon Master of this reflection app.
        Your goal is to provide brutal awareness.
        You adapt your tone from provocative (Dissonance) to supportive (Discovery) based on the user's action consistency.
        Always look for contradictions between the user's actions and their Identity Statement.

        Provide your summary in three distinct sections, separated by double newlines:
        ALIGNMENT: (What they did right and aligned with their identity)
        CONTRADICTION: (Where they failed and contradicted their identity)
        CHALLENGE: (A provocative call to action or question for the next week)
        PROMPT;

        if ($this->user?->identity_statement) {
            $instructions .= "\n\nThe user's Identity Statement is: \"{$this->user->identity_statement}\"";
            $instructions .= "\nUse this as the primary benchmark for identifying contradictions.";
        }

        return $instructions;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [
            new FetchEntries($this->user),
        ];
    }
}
