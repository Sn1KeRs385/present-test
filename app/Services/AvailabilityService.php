<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ServiceOption;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function getAvailableSlots(Carbon $dateMsk, ServiceOption $option): array
    {
        if ($dateMsk->isSunday()) return [];

        $schedule = $option->service->schedules()
            ->where('day_of_week', $dateMsk->dayOfWeekIso)
            ->first();
        if (!$schedule) return [];

        $block = $option->duration_minutes + 30;
        $step = $option->slot_step_minutes ?? 30;

        $windowStart = $dateMsk->clone()->setTime(10, 0);
        $windowEnd   = $dateMsk->clone()->setTime(20, 0);

        $dayStart = $dateMsk->clone()->setTimeFromTimeString($schedule->start_time);
        if ($dayStart->lt($windowStart)) $dayStart = $windowStart;
        $dayEnd = $dateMsk->clone()->setTimeFromTimeString($schedule->end_time);
        if ($dayEnd->gt($windowEnd)) $dayEnd = $windowEnd;
        if ($dayEnd->diffInMinutes($dayStart) < $block) return [];

        $existing = Booking::query()
            ->where('service_option_id', $option->id)
            ->where('status', 'active')
            ->where('starts_at', '<', $dayEnd->clone()->utc())
            ->where('ends_at', '>', $dayStart->clone()->utc())
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        for ($start = $dayStart->clone(); $start->lte($dayEnd->clone()->subMinutes($block)); $start->addMinutes($step)) {
            $end = $start->clone()->addMinutes($block);
            $sUtc = $start->clone()->tz('UTC');
            $eUtc = $end->clone()->tz('UTC');
            $overlap = $existing->first(fn($b) => $b->starts_at < $eUtc && $b->ends_at > $sUtc);
            if (!$overlap) $slots[] = $start->format('H:i');
        }

        return $slots;
    }
}
