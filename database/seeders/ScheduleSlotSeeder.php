<?php

namespace Database\Seeders;

use App\Models\ScheduleSlot;
use Illuminate\Database\Seeder;

class ScheduleSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [
            '09:00:00',
            '10:30:00',
            '12:00:00',
            '13:30:00',
            '15:00:00',
            '16:30:00',
            '18:00:00',
            '19:30:00',
            '21:00:00',
        ];

        foreach ($slots as $time) {
            ScheduleSlot::query()->updateOrCreate(['time' => $time]);
        }
    }
}
