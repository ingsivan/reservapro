<?php

namespace Database\Factories;

use App\Models\BlockedDate;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'date' => now()->addWeek()->toDateString(),
            'reason' => 'Día festivo',
        ];
    }
}
