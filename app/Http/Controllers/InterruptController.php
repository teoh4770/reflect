<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\Entry;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;

class InterruptController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $currentTime = $now->format('H:i:s');

        // Find the active slot (the latest slot that has already passed today)
        $activeSlot = ScheduleSlot::query()
            ->where('time', '<=', $currentTime)
            ->orderBy('time', 'desc')
            ->first();

        if (!$activeSlot) {
            return $this->lockedResponse($now);
        }

        // Check if the user already submitted an entry for this specific slot today
        $alreadyAnswered = Entry::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('created_at', $now->toDateString())
            ->where('metadata->slot_id', $activeSlot->id)
            ->exists();

        if ($alreadyAnswered) {
            return $this->lockedResponse($now);
        }

        // Get IDs of prompts already used TODAY to ensure no repeats until reset
        $usedPromptIds = Entry::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('created_at', $now->toDateString())
            ->pluck('prompt_id');

        // get the currently active prompt that's not in used prompt ids
        // if exists, return active response
        $activePrompt = Prompt::query()
            ->where('ritual', 'interrupt')
            ->whereNotIn('id', $usedPromptIds)
            ->active()
            ->first();

        if ($activePrompt) {
            return response()->json([
                'status' => 'active',
                'prompt' => $activePrompt,
                'slot' => $activeSlot,
            ]);
        }

        $prompt = Prompt::query()
            ->where('ritual', 'interrupt')
            ->whereNotIn('id', $usedPromptIds)
            ->inactive()
            ->inRandomOrder()
            ->first();

        if (!$prompt) {
            return response()->json(['error' => 'No prompts available'], 404);
        }

        $prompt->update([
            'active' => true
        ]);

        return response()->json([
            'status' => 'active',
            'prompt' => $prompt,
            'slot' => $activeSlot,
        ]);
    }

    protected function lockedResponse($now)
    {
        $nextSlot = ScheduleSlot::query()
            ->where('time', '>', $now->format('H:i:s'))
            ->orderBy('time', 'asc')
            ->first();

        // If no more slots today, the next one is the first slot of tomorrow
        if (!$nextSlot) {
            $nextSlot = ScheduleSlot::query()
                ->orderBy('time', 'asc')
                ->first();
            $nextSlotTime = now()->addDay()->format('Y-m-d') . ' ' . $nextSlot->time;
        } else {
            $nextSlotTime = $now->toDateString() . ' ' . $nextSlot->time;
        }

        return response()->json([
            'status' => 'locked',
            'next_slot_at' => $nextSlotTime,
        ]);
    }
}
