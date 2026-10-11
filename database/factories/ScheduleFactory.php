<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ];
    }
}
