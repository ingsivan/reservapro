<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates a new business with sensible defaults so the owner can start
 * taking bookings as soon as they add a service.
 */
class BusinessSetup
{
    /** Default opening hours: Monday to Friday 9–18, Saturday 9–14. */
    private const DEFAULT_HOURS = [
        [1, '09:00', '18:00'],
        [2, '09:00', '18:00'],
        [3, '09:00', '18:00'],
        [4, '09:00', '18:00'],
        [5, '09:00', '18:00'],
        [6, '09:00', '14:00'],
    ];

    public function createFor(User $owner, string $name): Business
    {
        $business = $owner->business()->create([
            'name' => $name,
            'slug' => self::uniqueSlug($name),
        ]);

        foreach (self::DEFAULT_HOURS as [$day, $opensAt, $closesAt]) {
            $business->schedules()->create([
                'day_of_week' => $day,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ]);
        }

        return $business;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'negocio';
        $slug = $base;
        $suffix = 2;

        while (Business::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
