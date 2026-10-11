<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id' => User::factory()->state(['role' => Role::Admin]),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'timezone' => 'America/Mexico_City',
            'slot_interval_minutes' => 30,
            'min_notice_minutes' => 0,
            'booking_window_days' => 30,
            'cancellation_notice_hours' => 2,
            'requires_confirmation' => false,
        ];
    }

    public function requiringConfirmation(): static
    {
        return $this->state(['requires_confirmation' => true]);
    }

    /**
     * Open every day of the week between the given times.
     */
    public function openDaily(string $opensAt = '09:00', string $closesAt = '17:00'): static
    {
        return $this->afterCreating(function (Business $business) use ($opensAt, $closesAt) {
            foreach (range(0, 6) as $day) {
                $business->schedules()->create(['day_of_week' => $day, 'opens_at' => $opensAt, 'closes_at' => $closesAt]);
            }
        });
    }
}
