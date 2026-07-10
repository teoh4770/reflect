<?php

use App\Console\Commands\GenerateSummary;
use App\Console\Commands\ResetActivePrompt;
use App\Console\Commands\TriggerInterrupt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;

Schedule::command(TriggerInterrupt::class)->everyMinute();

Schedule::command(ResetActivePrompt::class)->daily();

Schedule::command(GenerateSummary::class)->weeklyOn(Carbon::SUNDAY);
