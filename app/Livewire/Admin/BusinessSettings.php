<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesBusiness;
use App\Livewire\Concerns\ShowsNotices;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Ajustes')]
class BusinessSettings extends Component
{
    use ManagesBusiness, ShowsNotices;

    /** Time zones offered in the form; any valid PHP time zone is accepted. */
    public const TIMEZONES = [
        'America/Mexico_City' => 'Ciudad de México',
        'America/Monterrey' => 'Monterrey',
        'America/Tijuana' => 'Tijuana',
        'America/Cancun' => 'Cancún',
        'America/Bogota' => 'Bogotá',
        'America/Lima' => 'Lima',
        'America/Santiago' => 'Santiago',
        'America/Argentina/Buenos_Aires' => 'Buenos Aires',
        'America/Caracas' => 'Caracas',
        'America/Guatemala' => 'Guatemala',
        'America/New_York' => 'Nueva York',
        'America/Los_Angeles' => 'Los Ángeles',
        'Europe/Madrid' => 'Madrid',
    ];

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $address = '';

    public string $phone = '';

    public string $timezone = '';

    public int|string $slotIntervalMinutes = 30;

    public int|string $minNoticeMinutes = 60;

    public int|string $bookingWindowDays = 30;

    public int|string $cancellationNoticeHours = 2;

    public bool $requiresConfirmation = false;

    public function mount(): void
    {
        $business = $this->business;

        $this->fill([
            'name' => $business->name,
            'slug' => $business->slug,
            'description' => (string) $business->description,
            'address' => (string) $business->address,
            'phone' => (string) $business->phone,
            'timezone' => $business->timezone,
            'slotIntervalMinutes' => $business->slot_interval_minutes,
            'minNoticeMinutes' => $business->min_notice_minutes,
            'bookingWindowDays' => $business->booking_window_days,
            'cancellationNoticeHours' => $business->cancellation_notice_hours,
            'requiresConfirmation' => $business->requires_confirmation,
        ]);
    }

    public function save(): void
    {
        $business = $this->business;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('businesses', 'slug')->ignore($business->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'timezone' => ['required', 'timezone:all'],
            'slotIntervalMinutes' => ['required', 'integer', Rule::in([10, 15, 20, 30, 45, 60])],
            'minNoticeMinutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'bookingWindowDays' => ['required', 'integer', 'min:1', 'max:180'],
            'cancellationNoticeHours' => ['required', 'integer', 'min:0', 'max:168'],
            'requiresConfirmation' => ['boolean'],
        ], [
            'slug.regex' => 'Usa solo minúsculas, números y guiones.',
            'slug.unique' => 'Esa dirección ya la usa otro negocio.',
        ], [
            'slotIntervalMinutes' => 'intervalo',
            'minNoticeMinutes' => 'anticipación mínima',
            'bookingWindowDays' => 'días de reserva',
            'cancellationNoticeHours' => 'aviso para cancelar',
        ]);

        $business->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?: null,
            'address' => $validated['address'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'timezone' => $validated['timezone'],
            'slot_interval_minutes' => (int) $validated['slotIntervalMinutes'],
            'min_notice_minutes' => (int) $validated['minNoticeMinutes'],
            'booking_window_days' => (int) $validated['bookingWindowDays'],
            'cancellation_notice_hours' => (int) $validated['cancellationNoticeHours'],
            'requires_confirmation' => $validated['requiresConfirmation'],
        ]);

        unset($this->business);
        $this->notify('Ajustes guardados.');
    }

    public function render(): View
    {
        return view('livewire.admin.business-settings');
    }
}
