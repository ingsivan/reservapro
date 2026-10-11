<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Mexico_City';

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 12 October 2026, 08:00 in Mexico City (UTC-6).
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', self::TZ));
    }

    #[Test]
    public function it_offers_every_interval_that_fits_inside_the_opening_hours(): void
    {
        $service = $this->serviceFor($this->business(), minutes: 60);

        $this->assertSame(['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00'], $this->times($service, '2026-10-13'));
    }

    #[Test]
    public function a_day_without_opening_hours_has_no_slots(): void
    {
        $business = $this->business(hours: [[2, '09:00', '13:00']]);

        $this->assertSame([], $this->times($this->serviceFor($business), '2026-10-14'));
    }

    #[Test]
    public function split_shifts_produce_slots_in_both_windows_but_not_across_the_break(): void
    {
        $business = $this->business(hours: [[2, '09:00', '11:00'], [2, '15:00', '17:00']]);
        $service = $this->serviceFor($business, minutes: 60);

        $this->assertSame(['09:00', '09:30', '10:00', '15:00', '15:30', '16:00'], $this->times($service, '2026-10-13'));
    }

    #[Test]
    public function slots_that_overlap_an_existing_appointment_are_hidden(): void
    {
        $business = $this->business();
        $service = $this->serviceFor($business, minutes: 60);
        $this->appointment($service, '2026-10-13 10:15', minutes: 30);

        $this->assertSame(['09:00', '11:00', '11:30', '12:00'], $this->times($service, '2026-10-13'));
    }

    #[Test]
    public function back_to_back_appointments_are_allowed(): void
    {
        $business = $this->business();
        $service = $this->serviceFor($business, minutes: 60);
        $this->appointment($service, '2026-10-13 10:00', minutes: 60);

        $times = $this->times($service, '2026-10-13');

        $this->assertContains('09:00', $times);
        $this->assertContains('11:00', $times);
        $this->assertNotContains('09:30', $times);
        $this->assertNotContains('10:30', $times);
    }

    #[Test]
    public function cancelled_appointments_free_their_slot(): void
    {
        $business = $this->business();
        $service = $this->serviceFor($business, minutes: 60);
        $this->appointment($service, '2026-10-13 10:00', minutes: 60, cancelled: true);

        $this->assertContains('10:00', $this->times($service, '2026-10-13'));
    }

    #[Test]
    public function appointments_of_another_business_do_not_matter(): void
    {
        $service = $this->serviceFor($this->business(), minutes: 60);
        $this->appointment($this->serviceFor($this->business()), '2026-10-13 10:00');

        $this->assertContains('10:00', $this->times($service, '2026-10-13'));
    }

    #[Test]
    public function blocked_dates_have_no_slots(): void
    {
        $business = $this->business();
        $business->blockedDates()->create(['date' => '2026-10-13', 'reason' => 'Festivo']);

        $this->assertSame([], $this->times($this->serviceFor($business), '2026-10-13'));
        $this->assertNotSame([], $this->times($this->serviceFor($business), '2026-10-14'));
    }

    #[Test]
    public function slots_before_the_minimum_notice_are_hidden(): void
    {
        $business = $this->business(minNotice: 120);

        // It is 08:00, so the first bookable start today is 10:00.
        $this->assertSame('10:00', $this->times($this->serviceFor($business, minutes: 60), '2026-10-12')[0]);
    }

    #[Test]
    public function past_days_and_days_beyond_the_booking_window_have_no_slots(): void
    {
        $business = $this->business(windowDays: 7);
        $service = $this->serviceFor($business);

        $this->assertSame([], $this->times($service, '2026-10-11'));
        $this->assertNotSame([], $this->times($service, '2026-10-19'));
        $this->assertSame([], $this->times($service, '2026-10-20'));
    }

    #[Test]
    public function an_overnight_window_continues_after_midnight(): void
    {
        $business = $this->business(hours: [[5, '22:00', '02:00']]);
        $service = $this->serviceFor($business, minutes: 60);

        $slots = app(AvailabilityService::class)->slotsFor($service, '2026-10-16');

        $this->assertSame(
            ['2026-10-16 22:00', '2026-10-16 22:30', '2026-10-16 23:00', '2026-10-16 23:30', '2026-10-17 00:00', '2026-10-17 00:30', '2026-10-17 01:00'],
            array_map(fn (CarbonImmutable $slot) => $slot->format('Y-m-d H:i'), $slots),
        );
        $this->assertTrue(app(AvailabilityService::class)->isAvailable($service, CarbonImmutable::parse('2026-10-17 00:30', self::TZ)));
    }

    #[Test]
    public function an_appointment_after_midnight_blocks_the_overnight_window(): void
    {
        $business = $this->business(hours: [[5, '22:00', '02:00']]);
        $service = $this->serviceFor($business, minutes: 60);
        $this->appointment($service, '2026-10-17 00:00', minutes: 60);

        $this->assertSame(['22:00', '22:30', '23:00', '01:00'], $this->times($service, '2026-10-16'));
    }

    #[Test]
    public function slots_are_calculated_in_the_business_time_zone(): void
    {
        $business = $this->business(timezone: 'Europe/Madrid', hours: [[2, '09:00', '10:00']]);
        $service = $this->serviceFor($business, minutes: 60);

        $slots = app(AvailabilityService::class)->slotsFor($service, '2026-10-13');

        $this->assertCount(1, $slots);
        // 09:00 in Madrid (UTC+2 in October) is 07:00 UTC.
        $this->assertSame('2026-10-13 07:00', $slots[0]->utc()->format('Y-m-d H:i'));
    }

    #[Test]
    public function a_day_change_of_daylight_saving_time_keeps_local_opening_hours(): void
    {
        // Spain moves clocks back on Sunday 25 October 2026.
        $business = $this->business(timezone: 'Europe/Madrid', hours: [[0, '09:00', '10:00'], [1, '09:00', '10:00']]);
        $service = $this->serviceFor($business, minutes: 60);

        $sunday = app(AvailabilityService::class)->slotsFor($service, '2026-10-25');
        $monday = app(AvailabilityService::class)->slotsFor($service, '2026-10-26');

        $this->assertSame('09:00', $sunday[0]->format('H:i'));
        $this->assertSame('08:00', $sunday[0]->utc()->format('H:i'));
        $this->assertSame('08:00', $monday[0]->utc()->format('H:i'));
    }

    #[Test]
    public function the_slot_interval_controls_the_grid(): void
    {
        $business = $this->business(interval: 15, hours: [[2, '09:00', '10:00']]);

        $this->assertSame(['09:00', '09:15', '09:30'], $this->times($this->serviceFor($business, minutes: 30), '2026-10-13'));
    }

    #[Test]
    public function is_available_rejects_times_off_the_grid_or_already_taken(): void
    {
        $business = $this->business();
        $service = $this->serviceFor($business, minutes: 60);
        $this->appointment($service, '2026-10-13 10:00');
        $availability = app(AvailabilityService::class);

        $this->assertTrue($availability->isAvailable($service, CarbonImmutable::parse('2026-10-13 11:00', self::TZ)));
        $this->assertFalse($availability->isAvailable($service, CarbonImmutable::parse('2026-10-13 10:00', self::TZ)));
        $this->assertFalse($availability->isAvailable($service, CarbonImmutable::parse('2026-10-13 11:10', self::TZ)));
        $this->assertFalse($availability->isAvailable($service, CarbonImmutable::parse('2026-10-13 12:30', self::TZ)));
    }

    #[Test]
    public function available_days_lists_only_days_with_free_slots(): void
    {
        $business = $this->business(windowDays: 7, hours: [[2, '09:00', '10:00'], [4, '09:00', '10:00']]);
        $service = $this->serviceFor($business, minutes: 60);
        $this->appointment($service, '2026-10-15 09:00');

        $days = app(AvailabilityService::class)->availableDays($service);

        $this->assertSame(['2026-10-13'], array_map(fn (CarbonImmutable $day) => $day->toDateString(), $days));
    }

    /**
     * @param  list<array{int, string, string}>|null  $hours
     */
    private function business(
        string $timezone = self::TZ,
        ?array $hours = null,
        int $minNotice = 0,
        int $windowDays = 30,
        int $interval = 30,
    ): Business {
        $business = Business::factory()->create([
            'timezone' => $timezone,
            'min_notice_minutes' => $minNotice,
            'booking_window_days' => $windowDays,
            'slot_interval_minutes' => $interval,
        ]);

        foreach ($hours ?? array_map(fn (int $day) => [$day, '09:00', '13:00'], range(0, 6)) as [$day, $opensAt, $closesAt]) {
            $business->schedules()->create(['day_of_week' => $day, 'opens_at' => $opensAt, 'closes_at' => $closesAt]);
        }

        return $business;
    }

    private function serviceFor(Business $business, int $minutes = 30): Service
    {
        return Service::factory()->for($business)->minutes($minutes)->create();
    }

    private function appointment(Service $service, string $localStart, int $minutes = 60, bool $cancelled = false): Appointment
    {
        $factory = Appointment::factory()->for($service)->for($service->business)
            ->at(CarbonImmutable::parse($localStart, $service->business->timezone)->utc(), $minutes);

        return ($cancelled ? $factory->cancelled() : $factory)->create();
    }

    /**
     * @return list<string>
     */
    private function times(Service $service, string $date): array
    {
        return array_map(
            fn (CarbonImmutable $slot) => $slot->format('H:i'),
            app(AvailabilityService::class)->slotsFor($service, $date),
        );
    }
}
