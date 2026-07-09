<?php

namespace App\Console\Commands;

use App\Models\Prompt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('prompts:reset')]
#[Description('Reset all active prompts')]
class ResetActivePrompts extends Command
{
    public function handle(): void
    {
        Prompt::query()
            ->where('active', true)
            ->update([
                'active' => false
            ]);
    }
}
