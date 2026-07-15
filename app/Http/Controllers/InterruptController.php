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
        $dateTime = now();

        if ($this->shouldBeLocked($dateTime->copy())) {
            return $this->lockedResponse($dateTime->copy());
        }

        $currentAvailableTimeSlot = $this->getCurrentSlot($dateTime->copy());

        $usedPromptIdsToday = $request->user()->entries()
            ->whereDate('created_at', $dateTime->toDateString())
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

    private function getCurrentSlot(CarbonInterface $time)
    {
        return ScheduleSlot::query()
            ->where('time', '<=', $time->format('H:i'))
            ->orderByDesc('time')
            ->first();
    }

    private function shouldBeLocked(CarbonInterface $dateTime)
    {
        $currentAvailableTimeSlot = $this->getCurrentSlot($dateTime);

        $alreadyAnswered = request()->user()->entries()
            ->whereDate('created_at', $dateTime->toDateString())
            ->where('metadata->slot_id', $currentAvailableTimeSlot?->id)
            ->exists();

        return is_null($currentAvailableTimeSlot) || ($currentAvailableTimeSlot && $alreadyAnswered);
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

    private function lockedResponse(CarbonInterface $dateTime)
    {
        $firstTimeSlot = ScheduleSlot::query()
            ->orderBy('time')
            ->first();

        $nextTimeSlot = ScheduleSlot::query()
            ->where('time', '>', $dateTime->format('H:i:s'))
            ->orderBy('time')
            ->first();

        if (is_null($nextTimeSlot)) {
            $tomorrow = $dateTime->addDay()->format('Y-m-d') . ' ' . $firstTimeSlot->time;
            $nextTimeSlot = $tomorrow;
        } else {
            $nextTimeSlot = $dateTime->format('Y-m-d') . ' ' . $nextTimeSlot->time;
        }

        $visionPromptsCount = Prompt::query()->motivationalDrivers()->count();
        $visionAnsweredCount = request()->user()->entries()
            ->motivationalDrivers()
            ->count();

        $visionCompleted = $visionPromptsCount === 0 || $visionAnsweredCount >= $visionPromptsCount;

        return response()->json([
            'status' => 'locked',
            'next_slot_at' => $nextTimeSlot,
            'vision_completed' => $visionCompleted,
            'vision_answered_count' => $visionAnsweredCount,
            'vision_total_count' => $visionPromptsCount,
        ]);
    }
}
