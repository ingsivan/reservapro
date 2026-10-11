<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(2)->setTime(10, 0);

        return [
            'service_id' => Service::factory(),
            'business_id' => fn (array $attributes) => Service::find($attributes['service_id'])->business_id,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'status' => AppointmentStatus::Confirmed,
            'price_cents' => 25000,
            'confirmed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => AppointmentStatus::Pending, 'confirmed_at' => null]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => AppointmentStatus::Cancelled, 'cancelled_at' => now()]);
    }

    /**
     * Place the appointment at a UTC start time with the given length.
     */
    public function at(\DateTimeInterface $startsAt, int $minutes = 60): static
    {
        return $this->state([
            'starts_at' => $startsAt,
            'ends_at' => CarbonImmutable::instance($startsAt)->addMinutes($minutes),
        ]);
    }
}
