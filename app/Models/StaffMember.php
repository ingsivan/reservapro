<?php

namespace App\Models;

use Database\Factories\StaffMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person who attends customers, with their own agenda. They offer some of
 * the business's services and work the business's opening hours unless they
 * have schedules of their own.
 */
#[Fillable(['business_id', 'name', 'title', 'color', 'is_active'])]
class StaffMember extends Model
{
    /** @use HasFactory<StaffMemberFactory> */
    use HasFactory;

    /**
     * Colors to tell people apart in the agenda: avatar and dot classes.
     *
     * @var array<string, array{label: string, avatar: string, dot: string}>
     */
    public const COLORS = [
        'teal' => ['label' => 'Turquesa', 'avatar' => 'bg-teal-100 text-teal-800', 'dot' => 'bg-teal-500'],
        'violet' => ['label' => 'Violeta', 'avatar' => 'bg-violet-100 text-violet-800', 'dot' => 'bg-violet-500'],
        'amber' => ['label' => 'Ámbar', 'avatar' => 'bg-amber-100 text-amber-800', 'dot' => 'bg-amber-500'],
        'sky' => ['label' => 'Azul', 'avatar' => 'bg-sky-100 text-sky-800', 'dot' => 'bg-sky-500'],
        'rose' => ['label' => 'Rosa', 'avatar' => 'bg-rose-100 text-rose-800', 'dot' => 'bg-rose-500'],
        'lime' => ['label' => 'Lima', 'avatar' => 'bg-lime-100 text-lime-800', 'dot' => 'bg-lime-600'],
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * Opening windows of their own. Empty means they work the business's hours.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The windows this person works: their own, or the business's.
     *
     * @return Collection<int, Schedule>
     */
    public function workingSchedules(): Collection
    {
        $own = $this->schedules()->get();

        return $own->isNotEmpty() ? $own : $this->business->schedules()->get();
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    /**
     * First name, for short labels like "Con Diego".
     */
    public function firstName(): string
    {
        return strtok(trim($this->name), ' ') ?: $this->name;
    }

    public function avatarClasses(): string
    {
        return (self::COLORS[$this->color] ?? self::COLORS['teal'])['avatar'];
    }

    public function dotClasses(): string
    {
        return (self::COLORS[$this->color] ?? self::COLORS['teal'])['dot'];
    }
}
