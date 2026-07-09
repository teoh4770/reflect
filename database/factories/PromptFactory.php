<?php

namespace Database\Factories;

use App\Models\Prompt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prompt>
 */
class PromptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'body' => $this->faker->sentence(),
            'ritual' => 'interrupt',
            'active' => false,
        ];
    }
}
