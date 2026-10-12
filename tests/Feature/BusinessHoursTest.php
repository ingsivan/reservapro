<?php

namespace Tests\Feature;

use App\Models\Business;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusinessHoursTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday.
        $this->travelTo(CarbonImmutable::parse('2026-10-12 12:00', 'America/Mexico_City'));

        $this->business = Business::factory()->create(['name' => 'Barbería El Clásico']);

        foreach ([1, 2, 3, 4, 5] as $day) {
            $this->business->schedules()->create(['day_of_week' => $day, 'opens_at' => '10:00', 'closes_at' => '14:00']);
            $this->business->schedules()->create(['day_of_week' => $day, 'opens_at' => '16:00', 'closes_at' => '20:00']);
        }

        $this->business->schedules()->create(['day_of_week' => 6, 'opens_at' => '22:00', 'closes_at' => '02:00']);
    }

    #[Test]
    public function days_with_the_same_hours_are_grouped(): void
    {
        $this->assertSame([
            ['days' => 'Lun – Vie', 'ranges' => ['10:00 – 14:00', '16:00 – 20:00']],
            ['days' => 'Sábado', 'ranges' => ['22:00 – 02:00']],
            ['days' => 'Domingo', 'ranges' => []],
        ], $this->business->compactWeeklyHours());
    }

    #[Test]
    public function the_badge_says_when_the_business_closes(): void
    {
        $this->assertSame(['isOpen' => true, 'label' => 'Abierto · cierra a las 14:00'], $this->business->fresh()->openingStatus());
    }

    #[Test]
    public function the_badge_says_when_the_business_opens_next(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 15:00', 'America/Mexico_City'));
        $this->assertSame('Cerrado · abre hoy a las 16:00', $this->business->fresh()->openingStatus()['label']);

        $this->travelTo(CarbonImmutable::parse('2026-10-12 21:00', 'America/Mexico_City'));
        $this->assertSame('Cerrado · abre mañana a las 10:00', $this->business->fresh()->openingStatus()['label']);
    }

    #[Test]
    public function blocked_days_and_overnight_windows_are_respected(): void
    {
        $this->business->blockedDates()->create(['date' => '2026-10-13', 'reason' => 'Festivo']);
        $this->travelTo(CarbonImmutable::parse('2026-10-12 21:00', 'America/Mexico_City'));
        $this->assertSame('Cerrado · abre el miércoles a las 10:00', $this->business->fresh()->openingStatus()['label']);

        // Saturday's 22:00 – 02:00 window is still open early on Sunday.
        $this->travelTo(CarbonImmutable::parse('2026-10-18 01:00', 'America/Mexico_City'));
        $this->assertSame('Abierto · cierra a las 02:00', $this->business->fresh()->openingStatus()['label']);
    }

    #[Test]
    public function the_avatar_uses_the_initials_of_the_main_words(): void
    {
        $this->assertSame('BC', $this->business->initials());
        $this->assertSame('E', Business::factory()->make(['name' => 'El'])->initials());
    }
}
