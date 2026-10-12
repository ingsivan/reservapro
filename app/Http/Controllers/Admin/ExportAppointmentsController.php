<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusinessReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads the appointments of the reports period as a CSV file that opens
 * in Excel, Numbers or Google Sheets.
 */
class ExportAppointmentsController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $months = (int) $request->query('meses', 6);
        $report = new BusinessReport($request->user()->business, in_array($months, BusinessReport::PERIODS, true) ? $months : 6);
        $filename = sprintf('citas-%s-%s.csv', $report->from()->format('Y-m'), $report->until()->subDay()->format('Y-m'));

        return response()->streamDownload(function () use ($report) {
            $output = fopen('php://output', 'w');
            // Byte order mark, so Excel reads the accents as UTF-8.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Código', 'Fecha', 'Inicio', 'Fin', 'Servicio', 'Atiende', 'Cliente', 'Correo', 'Teléfono', 'Estado', 'Precio', 'Notas'], escape: '');

            foreach ($report->appointments() as $appointment) {
                fputcsv($output, [
                    $appointment->code,
                    $appointment->localStartsAt()->toDateString(),
                    $appointment->localStartsAt()->format('H:i'),
                    $appointment->localEndsAt()->format('H:i'),
                    $appointment->service->name,
                    $appointment->staffMember?->name,
                    $appointment->customer_name,
                    $appointment->customer_email,
                    $appointment->customer_phone,
                    $appointment->status->label(),
                    number_format($appointment->price_cents / 100, 2, '.', ''),
                    $appointment->notes,
                ], escape: '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
