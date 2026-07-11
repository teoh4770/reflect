<?php

namespace Database\Seeders;

use App\Models\Prompt;
use Illuminate\Database\Seeder;

class VisionSeeder extends Seeder
{
    public function run(): void
    {
        $pain = [
            [
                'body' => 'What is the dull and persistent dissatisfaction you’ve learned to live with?',
                'tooltip' => 'Not the deep suffering but what you’ve learned to tolerate.',
            ],
            [
                'body' => 'What do you complain about repeatedly but never actually change?',
                'tooltip' => 'Write down the three complaints you’ve voiced most often in the past year.',
            ],
            [
                'body' => 'For each complaint: What would someone who watched your behavior (not your words) conclude that you actually want?',
                'tooltip' => null,
            ],
            [
                'body' => 'What truth about your current life would be unbearable to admit to someone you deeply respect?',
                'tooltip' => null,
            ],
        ];

        $antiVisions = [
            [
                'body' => 'If absolutely nothing changes for the next five years, describe an average Tuesday.',
                'tooltip' => 'Where do you wake up? What does your body feel like? What’s the first thing you think about? Who’s around you? What do you do between 9am and 6pm? How do you feel at 10pm?',
            ],
            [
                'body' => 'If nothing changes for the next ten years, what have you missed?',
                'tooltip' => 'What opportunities closed? Who gave up on you? What do people say about you when you’re not in the room?',
            ],
            [
                'body' => 'You’re at the end of your life. You lived the safe version. You never broke the pattern. What was the cost?',
                'tooltip' => 'What did you never let yourself feel, try, or become?',
            ],
            [
                'body' => 'Who in your life is already living the future you just described?',
                'tooltip' => 'Someone five, ten, twenty years ahead on the same trajectory? What do you feel when you think about becoming them?',
            ],
            [
                'body' => 'What identity would you have to give up to actually change?',
                'tooltip' => '(”I am the type of person who...”) What would it cost you socially to no longer be that person?',
            ],
            [
                'body' => 'What is the most embarrassing reason you haven’t changed?',
                'tooltip' => 'The one that makes you sound weak, scared, or lazy rather than reasonable?',
            ],
            [
                'body' => 'If your current behavior is a form of self-protection, what exactly are you protecting?',
                'tooltip' => 'And what is that protection costing you?',
            ],
        ];

        $visions = [
            [
                'body' => 'If you could snap your fingers and be living a different life in three years, what do you actually want?',
                'tooltip' => 'Forget practicality for a minute. Not what’s realistic, what you actually want. What does an average Tuesday look like?',
            ],
            [
                'body' => 'What would you have to believe about yourself for that life to feel natural rather than forced?',
                'tooltip' => 'Write the identity statement: “I am the type of person who...”',
            ],
            [
                'body' => 'What is one thing you would do this week if you were already that person?',
                'tooltip' => null,
            ],
        ];

        foreach ($pain as $prompt) {
            Prompt::query()->withTrashed()->firstOrCreate([
                'ritual' => 'pain',
                'body' => $prompt['body'],
            ], [
                'tooltip' => $prompt['tooltip'],
            ]);
        }

        foreach ($antiVisions as $prompt) {
            Prompt::query()->withTrashed()->firstOrCreate([
                'ritual' => 'anti-vision',
                'body' => $prompt['body'],
            ], [
                'tooltip' => $prompt['tooltip'],
            ]);
        }

        foreach ($visions as $prompt) {
            Prompt::query()->withTrashed()->firstOrCreate([
                'ritual' => 'vision',
                'body' => $prompt['body'],
            ], [
                'tooltip' => $prompt['tooltip'],
            ]);
        }
    }
}
