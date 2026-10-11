<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * `starts_at` and `ends_at` are stored in UTC. Use localStartsAt() and
 * localEndsAt() to show them in the business's time zone.
 */
#[Fillable([
    'code', 'business_id', 'service_id', 'user_id', 'customer_name', 'customer_email',
    'customer_phone', 'notes', 'starts_at', 'ends_at', 'status', 'price_cents',
    'confirmed_at', 'cancelled_at', 'cancelled_by', 'reminder_sent_at',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            $appointment->code ??= self::generateCode();
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'reminder_sent_at' => 'immutable_datetime',
            'status' => AppointmentStatus::class,
            'cancelled_by' => CancelledBy::class,
            'price_cents' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Appointments that still hold their time slot.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', AppointmentStatus::blocking());
    }

    /**
     * Appointments that overlap the given UTC range.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $query->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc());
    }

    public function localStartsAt(): CarbonImmutable
    {
        return $this->starts_at->setTimezone($this->business->timezone);
    }

    public function localEndsAt(): CarbonImmutable
    {
        return $this->ends_at->setTimezone($this->business->timezone);
    }

    /**
     * Human date for emails and pages, e.g. "lunes 13 de octubre de 2026, 10:30 a 11:00".
     */
    public function whenLabel(): string
    {
        $start = $this->localStartsAt();

        return $start->translatedFormat('l j \\d\\e F \\d\\e Y, H:i').' a '.$this->localEndsAt()->format('H:i');
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_cents);
    }

    public function hasStarted(): bool
    {
        return $this->starts_at->isPast();
    }

    /**
     * Customers may cancel an active appointment until the business's notice period.
     */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->status->isActive()
            && now()->addHours($this->business->cancellation_notice_hours)->lte($this->starts_at);
    }

    /**
     * Signed link that lets the customer manage the appointment without an account.
     */
    public function manageUrl(): string
    {
        return URL::signedRoute('appointments.manage', ['appointment' => $this->code]);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Short, unambiguous reference the customer can read over the phone.
     */
    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
            $code = strtr($code, ['0' => 'X', 'O' => 'Y', '1' => 'Z', 'I' => 'W', 'L' => 'V']);
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
