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
            '11:00:00',
            '13:30:00',
            '15:15:00',
            '17:00:00',
            '19:30:00',
            '21:00:00',
        ];

        foreach ($slots as $time) {
            ScheduleSlot::query()->updateOrCreate(['time' => $time]);
        }
    }
}
