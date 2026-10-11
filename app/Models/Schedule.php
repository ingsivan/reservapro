<?php

namespace App\Models;

use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One opening window on a day of the week, e.g. Monday 09:00–14:00.
 * A day can have several windows (split shifts). When `closes_at` is not
 * after `opens_at`, the window runs past midnight into the next day.
 */
#[Fillable(['business_id', 'day_of_week', 'opens_at', 'closes_at'])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    /** Day names indexed like Carbon's dayOfWeek (0 = Sunday). */
    public const DAY_NAMES = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function dayName(int $dayOfWeek): string
    {
        return self::DAY_NAMES[$dayOfWeek];
    }

    public function crossesMidnight(): bool
    {
        return $this->closesAtTime() <= $this->opensAtTime();
    }

    public function opensAtTime(): string
    {
        return substr($this->opens_at, 0, 5);
    }

    public function closesAtTime(): string
    {
        return substr($this->closes_at, 0, 5);
    }

    public function label(): string
    {
        return "{$this->opensAtTime()} – {$this->closesAtTime()}";
    }
}
