<?php

namespace App\Http\Controllers;

use App\Actions\CreateWeeklySummaryAction;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function store(Request $request, CreateWeeklySummaryAction $createWeeklySummaryAction)
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

        $summary = $createWeeklySummaryAction->execute($today->copy(), $request->user());

        return response()->json($summary, Response::HTTP_CREATED);
    }
}
