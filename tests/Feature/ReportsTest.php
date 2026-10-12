<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Livewire\Admin\Reports;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use App\Services\BusinessReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Service $haircut;

    private Service $beard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', 'America/Mexico_City'));
        $this->business = Business::factory()->create(['name' => 'Barbería El Clásico']);
        $this->haircut = Service::factory()->for($this->business)->create(['name' => 'Corte clásico']);
        $this->beard = Service::factory()->for($this->business)->create(['name' => 'Arreglo de barba']);
    }

    #[Test]
    public function it_adds_up_income_and_appointments_per_month(): void
    {
        $this->appointment($this->haircut, '2026-10-05 10:00', 30000);
        $this->appointment($this->haircut, '2026-10-06 10:00', 30000, AppointmentStatus::Completed);
        $this->appointment($this->beard, '2026-10-07 10:00', 15000, AppointmentStatus::Pending);
        $this->appointment($this->beard, '2026-10-08 10:00', 15000, AppointmentStatus::Cancelled);
        $this->appointment($this->beard, '2026-10-09 10:00', 15000, AppointmentStatus::NoShow);
        $this->appointment($this->haircut, '2026-08-20 10:00', 30000);

        $report = new BusinessReport($this->business, 3);
        $monthly = $report->monthly();

        $this->assertSame(['2026-08', '2026-09', '2026-10'], array_map(fn (array $row) => $row['month']->format('Y-m'), $monthly));
        $this->assertSame(['revenueCents' => 30000, 'appointments' => 1, 'cancelled' => 0, 'noShows' => 0], array_diff_key($monthly[0], ['month' => true]));
        $this->assertSame(0, $monthly[1]['appointments']);
        $this->assertSame(['revenueCents' => 60000, 'appointments' => 4, 'cancelled' => 1, 'noShows' => 1], array_diff_key($monthly[2], ['month' => true]));

        $totals = $report->totals();
        $this->assertSame(90000, $totals['revenueCents']);
        $this->assertSame(5, $totals['appointments']);
        $this->assertSame(30000, $totals['averageTicketCents']);
        $this->assertSame(1 / 6, $totals['cancellationRate']);
    }

    #[Test]
    public function months_follow_the_business_time_zone_and_leave_out_older_appointments(): void
    {
        // 23:30 on September 30 in Mexico City is already October in UTC.
        $this->appointment($this->haircut, '2026-09-30 23:30', 30000);
        $this->appointment($this->haircut, '2026-07-31 10:00', 30000);

        $monthly = (new BusinessReport($this->business, 3))->monthly();

        $this->assertSame([0, 1, 0], array_column($monthly, 'appointments'));
    }

    #[Test]
    public function services_are_sorted_by_income_with_their_share(): void
    {
        $this->appointment($this->beard, '2026-10-05 10:00', 15000);
        $this->appointment($this->haircut, '2026-10-06 10:00', 30000);
        $this->appointment($this->haircut, '2026-10-07 10:00', 30000);
        $this->appointment($this->haircut, '2026-10-08 10:00', 30000, AppointmentStatus::Cancelled);

        $services = (new BusinessReport($this->business))->byService();

        $this->assertSame([
            ['name' => 'Corte clásico', 'appointments' => 2, 'revenueCents' => 60000, 'share' => 0.8],
            ['name' => 'Arreglo de barba', 'appointments' => 1, 'revenueCents' => 15000, 'share' => 0.2],
        ], $services);
    }

    #[Test]
    public function the_reports_page_shows_totals_and_switches_period(): void
    {
        $this->appointment($this->haircut, '2026-10-05 10:00', 30000);
        $this->appointment($this->beard, '2026-03-05 10:00', 15000);

        Livewire::actingAs($this->business->owner)
            ->test(Reports::class)
            ->assertSee('Ingresos por mes')
            ->assertSee('$300.00')
            ->assertDontSee('$450.00')
            ->call('setPeriod', 12)
            ->assertSet('months', 12)
            ->assertSee('$450.00')
            ->call('setPeriod', 5)
            ->assertSet('months', 6);
    }

    #[Test]
    public function the_reports_page_has_an_empty_state(): void
    {
        $this->actingAs($this->business->owner)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Todavía no hay citas en este periodo');
    }

    #[Test]
    public function the_owner_downloads_the_appointments_as_csv(): void
    {
        $this->appointment($this->haircut, '2026-10-05 10:00', 30000, attributes: [
            'customer_name' => 'Ana López',
            'customer_email' => 'ana@example.com',
            'notes' => 'Trae su foto, "corte bajo"',
        ]);
        $this->appointment($this->beard, '2026-03-05 10:00', 15000);
        $other = Business::factory()->create();
        Appointment::factory()->for(Service::factory()->for($other))->create(['customer_name' => 'Cliente ajeno']);

        $response = $this->actingAs($this->business->owner)->get(route('admin.reports.export', ['meses' => 3]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertDownload('citas-2026-08-2026-10.csv');

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = explode("\n", trim(substr($csv, 3)));
        $this->assertCount(2, $lines);
        $this->assertSame('Código,Fecha,Inicio,Fin,Servicio,Cliente,Correo,Teléfono,Estado,Precio,Notas', $lines[0]);
        $this->assertStringContainsString(',2026-10-05,10:00,11:00,"Corte clásico","Ana López",ana@example.com,', $lines[1]);
        $this->assertStringEndsWith(',Confirmada,300.00,"Trae su foto, ""corte bajo"""', $lines[1]);
    }

    #[Test]
    public function an_unknown_period_falls_back_to_six_months(): void
    {
        $this->actingAs($this->business->owner)
            ->get(route('admin.reports.export', ['meses' => 99]))
            ->assertDownload('citas-2026-05-2026-10.csv');
    }

    #[Test]
    public function customers_and_guests_cannot_export(): void
    {
        $this->get(route('admin.reports.export'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => Role::Client]))->get(route('admin.reports.export'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function appointment(Service $service, string $localStart, int $priceCents, AppointmentStatus $status = AppointmentStatus::Confirmed, array $attributes = []): Appointment
    {
        return Appointment::factory()
            ->for($service)
            ->at(CarbonImmutable::parse($localStart, 'America/Mexico_City')->utc())
            ->create(['status' => $status, 'price_cents' => $priceCents, ...$attributes]);
    }
}
