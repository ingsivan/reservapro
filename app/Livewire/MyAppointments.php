<?php

namespace App\Livewire;

use App\Enums\CancelledBy;
use App\Livewire\Concerns\ShowsNotices;
use App\Models\Appointment;
use App\Services\AppointmentBooker;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mis citas')]
class MyAppointments extends Component
{
    use ShowsNotices;

    public function cancel(int $appointmentId, AppointmentBooker $booker): void
    {
        $appointment = auth()->user()->appointments()->with('business.owner', 'service')->findOrFail($appointmentId);

        if (! $appointment->canBeCancelledByCustomer()) {
            $this->notify('Esta cita ya no se puede cancelar en línea.', isError: true);

            return;
        }

        $booker->cancel($appointment, CancelledBy::Customer);
        unset($this->upcoming, $this->past);
        $this->notify('Cancelaste tu cita. El negocio ya fue avisado.');
    }

    /**
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        return auth()->user()->appointments()
            ->with('business', 'service')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function past(): Collection
    {
        return auth()->user()->appointments()
            ->with('business', 'service')
            ->where('starts_at', '<', now())
            ->latest('starts_at')
            ->limit(20)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.my-appointments');
    }
}
