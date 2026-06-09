<?php

namespace Database\Seeders;

use App\Models\Prompt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prompts = [
            ['ritual' => 'interrupt', 'body' => 'What are you avoiding right now that you know would move the needle?'],
            ['ritual' => 'interrupt', 'body' => 'Is your current activity a distraction or a priority? Be honest.'],
            ['ritual' => 'interrupt', 'body' => 'If you died tonight, would you be proud of how you spent this last hour?'],
            ['ritual' => 'interrupt', 'body' => 'What "forbidden action" are you justifying to yourself at this moment?'],
            ['ritual' => 'interrupt', 'body' => 'Who are you trying to impress with your current "busyness"?'],
            ['ritual' => 'interrupt', 'body' => 'Stop. Look around. What in your environment is tolerating mediocrity?'],
            ['ritual' => 'interrupt', 'body' => 'If your future self saw you right now, would they be disgusted or inspired?'],
            ['ritual' => 'interrupt', 'body' => 'What is the most uncomfortable truth you are currently ignoring?'],
        ];

        foreach ($prompts as $prompt) {
            Prompt::query()->create($prompt);
        }
    }
}
