<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WeeklySummary;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $this->call([
            PromptSeeder::class,
            ScheduleSlotSeeder::class,
        ]);

        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser && !WeeklySummary::query()->where('user_id', $testUser->id)->exists()) {
            WeeklySummary::query()->create([
                'user_id' => $testUser->id,
                'identity_snapshot' => 'I am a disciplined athlete.',
                'week_start' => now()->startOfWeek()->subWeek()->toDateString(),
                'week_end' => now()->endOfWeek()->subWeek()->toDateString(),
                'content' => "(Note: This is a sample summary for your reference.) You've been largely aligned with your identity statement this week. However, there were a few instances where you avoided challenging tasks. Moving forward, try to tackle the hardest thing first.",
            ]);
        }
    }
}
