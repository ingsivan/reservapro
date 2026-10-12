<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class TimeSlots
{
    /**
     * Split start times into morning, afternoon and evening, skipping empty groups.
     *
     * @param  list<CarbonImmutable>  $slots
     * @return list<array{label: string, icon: string, slots: list<CarbonImmutable>}>
     */
    public static function groupByPartOfDay(array $slots): array
    {
        $groups = [
            ['label' => 'Mañana', 'icon' => 'sun', 'slots' => []],
            ['label' => 'Tarde', 'icon' => 'sunset', 'slots' => []],
            ['label' => 'Noche', 'icon' => 'moon', 'slots' => []],
        ];

        foreach ($slots as $start) {
            $groups[match (true) {
                $start->hour < 12 => 0,
                $start->hour < 19 => 1,
                default => 2,
            }]['slots'][] = $start;
        }

        return array_values(array_filter($groups, fn (array $group) => $group['slots'] !== []));
    }
}
