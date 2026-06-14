<?php

namespace App\Http\Controllers;

use App\Agents\Challenger;
use App\Models\User;
use App\Models\WeeklySummary;
use Illuminate\Http\Request;
use Laravel\Ai\Facades\Ai;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $summaries = WeeklySummary::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('week_end', 'desc')
            ->get();

        return response()->json([
            'summaries' => $summaries,
            'is_sunday' => now()->dayOfWeek === 0,
            'identity_statement' => $request->user()->identity_statement,
        ]);
    }

    public function store(Request $request)
    {
        $now = now();

        if ($now->dayOfWeek !== 0) { // 0 is Sunday
            return response()->json([
                'error' => 'The "Finish the Week" ritual is only available on Sundays.'
            ], 403);
        }

        $user = $request->user();
        $weekStart = $now->startOfWeek()->toDateString();
        $weekEnd = $now->endOfWeek()->toDateString();

        // Check if summary already exists for this week
        $exists = WeeklySummary::query()
            ->where('user_id', $user->id)
            ->where('week_start', $weekStart)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'You have already finished this week.'
            ], 422);
        }

        $agent = new Challenger($user);
        $loot = $agent->prompt("Summarize my week from {$weekStart} to {$weekEnd}. Identify contradictions with my Identity Statement.");

        $summary = WeeklySummary::create([
            'user_id' => $user->id,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'identity_snapshot' => $user->identity_statement,
            'content' => (string) $loot,
        ]);

        return response()->json($summary, 201);
    }
}
