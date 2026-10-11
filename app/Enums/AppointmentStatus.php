<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Por confirmar',
            self::Confirmed => 'Confirmada',
            self::Completed => 'Atendida',
            self::NoShow => 'No asistió',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * Tailwind classes for the status badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            self::Confirmed => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
            self::Completed => 'bg-sky-50 text-sky-800 ring-sky-600/20',
            self::NoShow => 'bg-slate-100 text-slate-700 ring-slate-500/20',
            self::Cancelled => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        };
    }

    /**
     * Statuses that keep their time slot taken.
     *
     * @return list<self>
     */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed, self::Completed, self::NoShow];
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }
}
