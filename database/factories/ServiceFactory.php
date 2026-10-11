<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->randomElement(['Corte de cabello', 'Consulta', 'Masaje', 'Manicura', 'Limpieza facial']),
            'duration_minutes' => 60,
            'price_cents' => fake()->numberBetween(10, 80) * 1000,
            'is_active' => true,
        ];
    }

    public function minutes(int $minutes): static
    {
        return $this->state(['duration_minutes' => $minutes]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
