<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisionController extends Controller
{
    public function index(Request $request)
    {
        $visionPrompts = Prompt::query()
            ->whereIn('ritual', ['pain', 'anti-vision', 'vision'])
            ->get();

        $entries = $request->user()->entries()
            ->whereIn('prompt_id', $visionPrompts->pluck('id'))
            ->get()
            ->keyBy('prompt_id');

        return Inertia::render('Vision', [
            'prompts' => $visionPrompts,
            'entries' => $entries,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt_id' => ['required', 'exists:prompts,id'],
            'body' => ['required', 'string'],
        ]);

        $request->user()->entries()->create($validated);

        return redirect()->route('vision.index');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $entry = $request->user()->entries()->findOrFail($id);
        $entry->update($validated);

        return redirect()->route('vision.index');
    }
}
