<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\Entry;
use Illuminate\Http\Request;

class InterruptController extends Controller
{
    public function index()
    {
        $prompt = Prompt::query()->where('ritual', 'interrupt')->inRandomOrder()->firstOrFail();
        return response()->json($prompt);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt_id' => 'required|exists:prompts,id',
            'body' => 'required|string',
        ]);

        $entry = Entry::query()->create($validated);

        return response()->json($entry, 201);
    }
}
