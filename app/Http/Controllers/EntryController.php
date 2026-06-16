<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->user()->entries()
            ->with('prompt')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($entry) {
                return $entry->created_at->startOfWeek()->format('Y-m-d');
            });

        return Inertia::render('Journal', [
            'entriesByWeek' => $entries
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt_id' => 'required|exists:prompts,id',
            'body' => 'required|string',
            'slot_id' => 'required|exists:schedule_slots,id',
        ]);

        $entry = $request->user()->entries()->create([
            'prompt_id' => $validated['prompt_id'],
            'body' => $validated['body'],
            'metadata' => ['slot_id' => (int)$validated['slot_id']]
        ]);

        return response()->json($entry, 201);
    }
}
