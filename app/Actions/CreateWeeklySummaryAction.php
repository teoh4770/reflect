<?php

namespace App\Actions;

use App\Agents\Challenger;
use App\Models\User;
use App\Models\WeeklySummary;
use Carbon\CarbonInterface;
use Laravel\Ai\Responses\AgentResponse;

class CreateWeeklySummaryAction
{
    public function execute(CarbonInterface $today, User $user): WeeklySummary
    {
        $weekStart = $today->copy()->startOfWeek()->toDateString();
        $weekEnd = $today->copy()->endOfWeek()->toDateString();

        $generatedWeeklySummary = $this->generateWeeklySummary($weekStart, $weekEnd, $user);

        return WeeklySummary::query()->create([
            'user_id' => $user->id,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'identity_snapshot' => $user->identity_statement,
            'content' => (string)$generatedWeeklySummary
        ]);
    }

    private function generateWeeklySummary(string $weekStart, string $weekEnd, User $user): AgentResponse
    {
        $agent = Challenger::make($user);
        return $agent->prompt("Summarize my week from $weekStart to $weekEnd. Identify contradictions with my Identity Statement.");
    }
}
