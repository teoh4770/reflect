<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWeeklySummaryJob;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('summary:generate')]
#[Description('Challenger generates a weekly summary.')]
class GenerateSummary extends Command
{
    public function handle(): void
    {
        $today = now();

        User::query()->chunk(100, function ($users) use ($today) {
            foreach ($users as $user) {
                GenerateWeeklySummaryJob::dispatch($user, $today->copy());
            }
        });
        
        $this->info('Dispatched weekly summary generation jobs.');
    }
}
