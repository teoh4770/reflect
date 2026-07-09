<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntryRequest;
use App\Models\Entry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->user()->entries()
            ->with('prompt')
            ->orderByDesc('created_at')
            ->get();

        $weeklyEntries = $entries->groupBy(function ($entry) {
            return $entry->created_at->startOfWeek()->format('Y-m-d');
        });

        return Inertia::render('Journal', [
            'entriesByWeek' => $weeklyEntries
        ]);
    }

    public function store(EntryRequest $request)
    {
        $validated = $request->validated();

        $entry = Entry::query()
            ->where('user_id', $request->user()->id)
            ->create([
                'prompt_id' => $validated['prompt_id'],
                'body' => $validated['body'],
                'metadata' => ['slot_id' => (int)$validated['slot_id']]
            ]);

        return response()->json($entry, 201);
    }
}
