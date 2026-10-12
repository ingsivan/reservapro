<?php

namespace Tests\Feature;

use App\Enums\CancelledBy;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\StaffMember;
use App\Services\AppointmentBooker;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Mexico_City';

    private Business $business;

    private Service $haircut;

    private StaffMember $diego;

    private StaffMember $sofia;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        // Monday 12 October 2026, 08:00 in Mexico City.
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', self::TZ));
        $this->business = Business::factory()->openDaily('09:00', '12:00')->create(['min_notice_minutes' => 0]);
        $this->haircut = Service::factory()->for($this->business)->minutes(60)->create();
        $this->diego = StaffMember::factory()->for($this->business)->offeringAllServices()->create(['name' => 'Diego Ramírez']);
        $this->sofia = StaffMember::factory()->for($this->business)->offeringAllServices()->create(['name' => 'Sofía Herrera']);
    }

    #[Test]
    public function a_time_stays_free_while_someone_on_the_team_is_free(): void
    {
        $this->appointment($this->diego, '2026-10-13 10:00');

        $this->assertContains('10:00', $this->times());
        $this->assertNotContains('10:00', $this->times($this->diego));
        $this->assertContains('10:00', $this->times($this->sofia));

        $this->appointment($this->sofia, '2026-10-13 10:00');

        $this->assertNotContains('10:00', $this->times());
    }

    #[Test]
    public function staff_members_with_their_own_hours_only_work_then(): void
    {
        $this->sofia->schedules()->create(['business_id' => $this->business->id, 'day_of_week' => 2, 'opens_at' => '15:00', 'closes_at' => '17:00']);

        $this->assertSame(['15:00', '15:30', '16:00'], $this->times($this->sofia));
        $this->assertSame([], $this->times($this->sofia, '2026-10-14'));
        $this->assertSame(['09:00', '09:30', '10:00', '10:30', '11:00', '15:00', '15:30', '16:00'], $this->times());
    }

    #[Test]
    public function only_active_staff_who_offer_the_service_count(): void
    {
        $beard = Service::factory()->for($this->business)->minutes(60)->create();
        $beard->staffMembers()->attach($this->sofia);
        $this->appointment($this->sofia, '2026-10-13 10:00', $beard);

        $this->assertNotContains('10:00', $this->times(service: $beard));

        $this->sofia->update(['is_active' => false]);

        $this->assertSame([], $this->times(service: $beard));
    }

    #[Test]
    public function appointments_from_before_the_team_block_everyone(): void
    {
        Appointment::factory()->for($this->haircut)->at(CarbonImmutable::parse('2026-10-13 10:00', self::TZ)->utc())->create();

        $this->assertNotContains('10:00', $this->times());
    }

    #[Test]
    public function booking_without_a_preference_goes_to_the_least_busy_person(): void
    {
        $this->appointment($this->diego, '2026-10-13 09:00');

        $appointment = $this->book('2026-10-13 11:00');

        $this->assertTrue($appointment->staffMember->is($this->sofia));
        $this->assertTrue($this->book('2026-10-13 11:00')->staffMember->is($this->diego));
        $this->expectException(SlotUnavailableException::class);
        $this->book('2026-10-13 11:00');
    }

    #[Test]
    public function booking_with_a_busy_person_fails_even_if_someone_else_is_free(): void
    {
        $this->appointment($this->diego, '2026-10-13 10:00');

        $this->expectException(SlotUnavailableException::class);
        $this->book('2026-10-13 10:00', $this->diego);
    }

    #[Test]
    public function a_person_who_does_not_offer_the_service_cannot_be_booked_for_it(): void
    {
        $this->sofia->services()->detach();

        $this->assertTrue($this->book('2026-10-13 10:00')->staffMember->is($this->diego));
        $this->expectException(SlotUnavailableException::class);
        $this->book('2026-10-13 11:00', $this->sofia);
    }

    #[Test]
    public function rescheduling_keeps_the_same_person(): void
    {
        $appointment = $this->book('2026-10-13 09:00', $this->sofia);
        $this->appointment($this->sofia, '2026-10-13 11:00');
        $booker = app(AppointmentBooker::class);

        $this->assertNotContains('11:00', array_map(fn ($slot) => $slot->format('H:i'), app(AvailabilityService::class)->slotsToReschedule($appointment)['2026-10-13']));

        $booker->reschedule($appointment, CarbonImmutable::parse('2026-10-13 10:00', self::TZ), CancelledBy::Customer);
        $this->assertTrue($appointment->fresh()->staffMember->is($this->sofia));

        $this->expectException(SlotUnavailableException::class);
        $booker->reschedule($appointment, CarbonImmutable::parse('2026-10-13 11:00', self::TZ), CancelledBy::Customer);
    }

    #[Test]
    public function a_business_without_active_staff_books_as_a_whole(): void
    {
        $this->diego->update(['is_active' => false]);
        $this->sofia->update(['is_active' => false]);

        $appointment = $this->book('2026-10-13 10:00');

        $this->assertNull($appointment->staff_member_id);
        $this->assertNotContains('10:00', $this->times());
    }

    /**
     * @return list<string>
     */
    private function times(?StaffMember $staffMember = null, string $date = '2026-10-13', ?Service $service = null): array
    {
        $service = ($service ?? $this->haircut)->fresh();
        $day = CarbonImmutable::parse($date, self::TZ);

        return array_map(
            fn (CarbonImmutable $slot) => $slot->format('H:i'),
            app(AvailabilityService::class)->slotsBetween($service, $day, $day, staffMember: $staffMember)[$date],
        );
    }

    private function appointment(StaffMember $staffMember, string $localStart, ?Service $service = null): Appointment
    {
        return Appointment::factory()
            ->for($service ?? $this->haircut)
            ->at(CarbonImmutable::parse($localStart, self::TZ)->utc())
            ->create(['staff_member_id' => $staffMember->id]);
    }

    private function book(string $localStart, ?StaffMember $staffMember = null): Appointment
    {
        return app(AppointmentBooker::class)->book(
            $this->haircut->fresh(),
            CarbonImmutable::parse($localStart, self::TZ),
            ['customer_name' => 'Ana López', 'customer_email' => 'ana@example.com'],
            staffMember: $staffMember,
        );
    }
}
