<?php

namespace MediaPlatform\Digest\Processing\Jobs;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DispatchDueLists implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lists = ListModel::where('enabled', true)->get();

        Log::info("DispatchDueLists: Checking {$lists->count()} enabled lists.");

        $dispatched = 0;

        foreach ($lists as $list) {
            if (! $this->isDue($list)) {
                continue;
            }

            Log::info("DispatchDueLists: List '{$list->name}' (ID {$list->id}) is due. Dispatching.");

            ProcessList::dispatch($list);
            $dispatched++;
        }

        Log::info("DispatchDueLists: Dispatched {$dispatched} lists for processing.");
    }

    /**
     * Determine if a list is due for processing based on its schedule fields.
     * Uses the list's timezone for all time comparisons.
     */
    private function isDue(ListModel $list): bool
    {
        $timezone = $list->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);

        $scheduledHour   = (int) substr($list->schedule_time, 0, 2);
        $scheduledMinute = (int) substr($list->schedule_time, 3, 2);

        // Allow a 5-minute window around the scheduled time
        $scheduledTime = $now->copy()->setTime($scheduledHour, $scheduledMinute, 0);
        $windowStart   = $scheduledTime->copy();
        $windowEnd     = $scheduledTime->copy()->addMinutes(5);

        if (! $now->between($windowStart, $windowEnd)) {
            return false;
        }

        return match ($list->schedule_frequency) {
            'daily'   => true,
            'weekly'  => $now->dayOfWeekIso === (int) $list->schedule_day,
            'monthly' => $this->isMonthlyDue($now, (int) $list->schedule_day),
            default   => false,
        };
    }

    /**
     * Handle monthly scheduling, accounting for months with fewer days.
     */
    private function isMonthlyDue(Carbon $now, int $scheduledDay): bool
    {
        $lastDayOfMonth = $now->daysInMonth;
        $effectiveDay = min($scheduledDay, $lastDayOfMonth);

        return $now->day === $effectiveDay;
    }
}
