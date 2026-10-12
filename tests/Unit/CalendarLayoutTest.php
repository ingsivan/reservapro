<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Support\CalendarLayout;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalendarLayoutTest extends TestCase
{
    #[Test]
    public function overlapping_appointments_share_the_width_and_the_rest_take_all_of_it(): void
    {
        $layout = CalendarLayout::lanes([
            $this->appointment(1, '09:00', '10:00'),
            $this->appointment(2, '09:30', '10:30'),
            $this->appointment(3, '10:00', '11:00'),
            $this->appointment(4, '11:00', '11:30'),
        ]);

        $this->assertSame([
            1 => ['lane' => 0, 'lanes' => 2],
            2 => ['lane' => 1, 'lanes' => 2],
            3 => ['lane' => 0, 'lanes' => 2],
            4 => ['lane' => 0, 'lanes' => 1],
        ], $layout);
    }

    private function appointment(int $id, string $start, string $end): Appointment
    {
        $appointment = new Appointment;
        $appointment->id = $id;
        $appointment->setRawAttributes([
            'id' => $id,
            'starts_at' => CarbonImmutable::parse("2026-10-13 {$start}")->format('Y-m-d H:i:s'),
            'ends_at' => CarbonImmutable::parse("2026-10-13 {$end}")->format('Y-m-d H:i:s'),
        ]);

        return $appointment;
    }
}
