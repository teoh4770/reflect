<?php

namespace Database\Seeders;

use App\Models\Prompt;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Avoid deleting existing prompts to prevent cascading deletion of user entries
        $prompts = [
            'What am I avoiding right now by doing what I’m doing?',
            'If someone filmed the last two hours, what would they conclude I want from my life?',
            'Am I moving toward the life I hate or the life I want?',
            'What’s the most important thing I’m pretending isn’t important?',
            'What did I do today out of identity protection rather than genuine desire?',
            'When did I feel most alive today? When did I feel most dead?',
            'What would change if I stopped needing people to see me as the identity I protect?',
            'Where in my life am I trading aliveness for safety?',
            'What’s the smallest version of the person I want to become that I could be tomorrow?',
        ];

        foreach ($prompts as $body) {
            Prompt::query()->withTrashed()->firstOrCreate([
                'ritual' => 'interrupt',
                'body' => $body,
            ]);
        }
    }
}
