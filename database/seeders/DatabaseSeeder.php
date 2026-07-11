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
            InterruptSeeder::class,
            VisionSeeder::class,
            ScheduleSlotSeeder::class,
        ]);

        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser && $testUser->weeklySummaries()->doesntExist()) {
            WeeklySummary::query()->create([
                'user_id' => $testUser->id,
                'identity_snapshot' => 'I am a disciplined athlete.',
                'week_start' => now()->startOfWeek()->subWeek()->toDateString(),
                'week_end' => now()->endOfWeek()->subWeek()->toDateString(),
                'content' => "(Note: This is a sample summary for your reference.)\n\nALIGNMENT: You've been largely aligned with your identity statement this week when you stayed consistent with your morning routine.\n\nCONTRADICTION: However, there were a few instances where you avoided challenging tasks by busying yourself with administrative work.\n\nCHALLENGE: Moving forward, try to tackle the hardest thing first. What would happen if you stopped hiding behind busywork?",
            ]);
        }
    }
}
