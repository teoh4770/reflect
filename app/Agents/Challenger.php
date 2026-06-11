<?php

namespace App\Agents;

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

    /**
     * Create a new Challenger agent instance.
     */
    public function __construct(public ?\App\Models\User $user = null)
    {
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $instructions = <<<'PROMPT'
        You are the "Challenger AI", the Dungeon Master of this reflection app.
        Your goal is to provide brutal awareness.
        You adapt your tone from provocative (Dissonance) to supportive (Discovery) based on the user's action consistency.
        Always look for contradictions between the user's actions and their Identity Statement.
        PROMPT;

        if ($this->user?->identity_statement) {
            $instructions .= "\n\nThe user's Identity Statement is: \"{$this->user->identity_statement}\"";
            $instructions .= "\nUse this as the primary benchmark for identifying contradictions.";
        }

        return $instructions;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
