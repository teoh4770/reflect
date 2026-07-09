<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\Entry;
use App\Models\ScheduleSlot;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class InterruptController extends Controller
{
    public function index(Request $request)
    {
        $time = now();

        if ($this->shouldBeLocked($time)) {
            return $this->lockedResponse($time);
        }

        $currentAvailableTimeSlot = ScheduleSlot::query()
            ->where('time', '<=', $time->toDateString())
            ->orderBy('time')
            ->first();

        $usedPromptIdsToday = $request->user()->entries()
            ->whereDate('created_at', $time->toDateString())
            ->pluck('prompt_id');

        $currentActivePrompt = $this->getCurrentActivePrompt($usedPromptIdsToday);
        if ($currentActivePrompt) {
            return response()->json([
                'status' => 'active',
                'prompt' => $currentActivePrompt,
                'slot' => $currentAvailableTimeSlot,
            ]);
        }

        $inactivePrompt = $this->getInactivePrompt($usedPromptIdsToday);
        if (!$inactivePrompt) {
            return response()->json(['error' => 'No prompts available'], Response::HTTP_NOT_FOUND);
        }

        $inactivePrompt->update([
            'active' => true
        ]);

        return response()->json([
            'status' => 'active',
            'prompt' => $inactivePrompt,
            'slot' => $currentAvailableTimeSlot,
        ]);
    }

    private function shouldBeLocked(CarbonInterface $time)
    {
        $currentAvailableTimeSlot = ScheduleSlot::query()
            ->where('time', '<=', $time->format('H:i'))
            ->orderByDesc('time')
            ->first();

        $alreadyAnswered = request()->user()->entries()
            ->whereDate('created_at', $time->toDateString())
            ->where('metadata->slot_id', $currentAvailableTimeSlot?->id)
            ->exists();

        return is_null($currentAvailableTimeSlot) || $alreadyAnswered;
    }

    private function getInactivePrompt(Collection $usedPromptIds)
    {
        return Prompt::query()
            ->interrupt()
            ->unused($usedPromptIds)
            ->inactive()
            ->inRandomOrder()
            ->first();
    }

    private function getCurrentActivePrompt(Collection $usedPromptIds)
    {
        return Prompt::query()
            ->interrupt()
            ->unused($usedPromptIds)
            ->active()
            ->first();
    }

    private function lockedResponse(CarbonInterface $now)
    {
        $firstTimeSlot = ScheduleSlot::query()
            ->orderBy('time')
            ->first();

        $nextTimeSlot = ScheduleSlot::query()
            ->where('time', '>', $now->format('H:i:s'))
            ->orderBy('time')
            ->first();

        if (is_null($nextTimeSlot)) {
            $tomorrow = $now->addDay()->format('Y-m-d') . ' ' . $firstTimeSlot->time;
            $nextTimeSlot = $tomorrow;
        } else {
            $nextTimeSlot = $now->format('Y-m-d') . ' ' . $nextTimeSlot->time;
        }

        return response()->json([
            'status' => 'locked',
            'next_slot_at' => $nextTimeSlot,
        ]);
    }
}
