<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\ScheduleSlot;
use App\Events\InterruptTriggered;

class TriggerInterrupts extends Command
{
    protected $signature = 'app:trigger-interrupts';
    protected $description = 'Trigger an interrupt event if the current time matches a schedule slot';

    public function handle()
    {
        $currentTime = now()->format('H:i');

        // Match the current hour and minute
        $slots = ScheduleSlot::all()->filter(function ($slot) use ($currentTime) {
            return Carbon::parse($slot->time)->format('H:i') === $currentTime;
        });

        if ($slots->isNotEmpty()) {
            event(new InterruptTriggered());
            $this->info("Interrupt triggered at {$currentTime}");
        } else {
            $this->info("No interrupt scheduled for {$currentTime}");
        }
    }
}
