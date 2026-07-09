<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\Prompt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'prompt_id' => Prompt::factory(),
            'body' => $this->faker->paragraph(),
            'metadata' => []
        ];
    }
}
