<?php

namespace App\Http\Controllers;

use App\Enums\CancelledBy;
use App\Models\Appointment;
use App\Services\AppointmentBooker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

/**
 * Lets a customer see and cancel their appointment from the signed link in
 * their email, without needing an account.
 */
class ManageAppointmentController extends Controller
{
    public function show(Appointment $appointment): View
    {
        $appointment->load('business', 'service');

        return view('appointments.manage', [
            'appointment' => $appointment,
            'cancelUrl' => URL::signedRoute('appointments.cancel', ['appointment' => $appointment->code]),
        ]);
    }

    /**
     * Download the appointment as an .ics file for any calendar app.
     */
    public function calendar(Appointment $appointment): Response
    {
        $appointment->load('business', 'service');

        return response($appointment->toIcalendar(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="cita-'.$appointment->code.'.ics"',
        ]);
    }

    public function cancel(Appointment $appointment, AppointmentBooker $booker): RedirectResponse
    {
        $appointment->load('business.owner', 'service');

        if (! $appointment->canBeCancelledByCustomer()) {
            return redirect($appointment->manageUrl())
                ->with('error', 'Esta cita ya no se puede cancelar en línea. Comunícate directamente con el negocio.');
        }

        $booker->cancel($appointment, CancelledBy::Customer);

        return redirect($appointment->manageUrl())->with('status', 'Tu cita fue cancelada. El negocio ya fue avisado.');
    }
}
