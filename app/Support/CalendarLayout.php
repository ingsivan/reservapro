<?php

namespace App\Support;

use App\Models\Appointment;

/**
 * Places the appointments of one day side by side when they overlap, like a
 * calendar app does: each one gets a lane, and every appointment in a group
 * of overlapping ones shares the same number of lanes.
 */
class CalendarLayout
{
    /**
     * @param  iterable<Appointment>  $appointments  Sorted by start time.
     * @return array<int, array{lane: int, lanes: int}> Keyed by appointment id.
     */
    public static function lanes(iterable $appointments): array
    {
        $layout = [];
        $group = [];
        $laneEnds = [];
        $groupEnd = null;

        $closeGroup = function () use (&$layout, &$group, &$laneEnds) {
            foreach ($group as $id) {
                $layout[$id]['lanes'] = count($laneEnds);
            }

            $group = [];
            $laneEnds = [];
        };

        foreach ($appointments as $appointment) {
            if ($groupEnd !== null && $appointment->starts_at->gte($groupEnd)) {
                $closeGroup();
                $groupEnd = null;
            }

            $lane = 0;

            while (isset($laneEnds[$lane]) && $laneEnds[$lane]->gt($appointment->starts_at)) {
                $lane++;
            }

            $laneEnds[$lane] = $appointment->ends_at;
            $group[] = $appointment->id;
            $layout[$appointment->id] = ['lane' => $lane, 'lanes' => 1];
            $groupEnd = $groupEnd === null || $appointment->ends_at->gt($groupEnd) ? $appointment->ends_at : $groupEnd;
        }

        $closeGroup();

        return $layout;
    }
}
