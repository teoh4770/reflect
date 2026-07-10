<?php

namespace App\Console\Commands;

use App\Models\Prompt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('prompt:reset')]
#[Description('Reset all active prompts')]
class ResetActivePrompt extends Command
{
    public function handle(): void
    {
        Prompt::active()->update([
            'active' => false
        ]);
    }
}
