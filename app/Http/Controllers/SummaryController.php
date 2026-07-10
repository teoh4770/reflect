<?php

namespace App\Http\Controllers;

use App\Agents\Challenger;
use App\Models\WeeklySummary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Ai\Responses\AgentResponse;
use Symfony\Component\HttpFoundation\Response;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $summaries = $request->user()->weeklySummaries()
            ->orderByDesc('week_end')
            ->get();

        $alreadyFinished = $request->user()->weeklySummaries()
            ->whereDate('week_start', now()->startOfWeek())
            ->exists();

        return response()->json([
            'summaries' => $summaries,
            'is_sunday' => now()->dayOfWeek === Carbon::SUNDAY,
            'identity_statement' => $request->user()->identity_statement,
            'already_finished' => $alreadyFinished,
        ]);
    }

    public function store(Request $request)
    {
        $today = now();
        $isSunday = $today->copy()->dayOfWeek === Carbon::SUNDAY;

        if (!$isSunday) {
            return response()->json([
                'error' => 'The "Finish the Week" ritual is only available on Sundays.'
            ], Response::HTTP_FORBIDDEN);
        }

        $summaryExists = $request->user()->weeklySummaries()
            ->whereDate('week_start', $today->copy()->startOfWeek())
            ->exists();

        if ($summaryExists) {
            return response()->json([
                'error' => 'You have already finished this week.'
            ], Response::HTTP_CONFLICT);
        }

        $weekStart = $today->copy()->startOfWeek()->toDateString();
        $weekEnd = $today->copy()->endOfWeek()->toDateString();

        $generatedWeeklySummary = $this->generateWeeklySummary($weekStart, $weekEnd);

        $summary = WeeklySummary::query()->create([
            'user_id' => $request->user()->id,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'identity_snapshot' => $request->user()->identity_statement,
            'content' => (string)$generatedWeeklySummary
        ]);

        return response()->json($summary, Response::HTTP_CREATED);
    }

    private function generateWeeklySummary(string $weekStart, string $weekEnd): AgentResponse
    {
        $agent = Challenger::make(request()->user());
        return $agent->prompt("Summarize my week from $weekStart to $weekEnd. Identify contradictions with my Identity Statement.");
    }
}
