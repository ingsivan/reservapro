<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Enums\Role;
use App\Models\Business;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demo business with services, opening hours and two weeks of appointments
 * around today, so the agenda looks alive on a fresh install.
 *
 * Admin: admin@reservapro.test / password. Customer: cliente@reservapro.test / password.
 *
 * It avoids model factories because Faker is a dev dependency and the
 * Docker image seeds the demo database in production mode.
 */
class DatabaseSeeder extends Seeder
{
    public const DEMO_ADMIN_EMAIL = 'admin@reservapro.test';

    public const DEMO_CLIENT_EMAIL = 'cliente@reservapro.test';

    public const DEMO_BUSINESS_SLUG = 'barberia-demo';

    private const CUSTOMERS = [
        'Carlos Méndez', 'Luis Hernández', 'Jorge Ramírez', 'Miguel Torres', 'Andrés Castillo',
        'Diego Morales', 'Fernando Ruiz', 'Ricardo Vega', 'Alejandro Navarro', 'Pablo Ortiz',
        'Sergio Romero', 'Javier Flores', 'Daniel Silva', 'Raúl Jiménez', 'Emilio Rojas',
        'Óscar Guzmán', 'Hugo Medina', 'Iván Cruz', 'Mateo Aguilar', 'Tomás Reyes',
    ];

    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin demo',
            'email' => self::DEMO_ADMIN_EMAIL,
            'password' => 'password',
            'role' => Role::Admin,
            'email_verified_at' => now(),
        ]);

        $client = User::create([
            'name' => 'Ana López',
            'email' => self::DEMO_CLIENT_EMAIL,
            'password' => 'password',
            'role' => Role::Client,
            'email_verified_at' => now(),
        ]);

        $business = $admin->business()->create([
            'name' => 'Barbería El Clásico',
            'slug' => self::DEMO_BUSINESS_SLUG,
            'description' => 'Cortes clásicos y modernos, barba y afeitado con navaja. Reserva tu lugar en menos de un minuto.',
            'address' => 'Av. Juárez 120, Centro, Ciudad de México',
            'phone' => '55 1234 5678',
            'timezone' => 'America/Mexico_City',
            'slot_interval_minutes' => 30,
            'min_notice_minutes' => 60,
            'booking_window_days' => 30,
            'cancellation_notice_hours' => 2,
            'requires_confirmation' => false,
        ]);

        foreach ([
            ['Corte de cabello', 'Lavado, corte a tijera o máquina y peinado.', 30, 20000],
            ['Corte y barba', 'Corte completo más perfilado y arreglo de barba.', 60, 32000],
            ['Arreglo de barba', 'Perfilado, toalla caliente y aceite.', 30, 15000],
            ['Afeitado clásico', 'Afeitado con navaja y toallas calientes.', 45, 22000],
            ['Corte infantil', 'Para menores de 12 años.', 30, 16000],
        ] as [$name, $description, $minutes, $price]) {
            $business->services()->create([
                'name' => $name,
                'description' => $description,
                'duration_minutes' => $minutes,
                'price_cents' => $price,
            ]);
        }

        foreach ([1, 2, 3, 4, 5] as $day) {
            $business->schedules()->create(['day_of_week' => $day, 'opens_at' => '10:00', 'closes_at' => '14:00']);
            $business->schedules()->create(['day_of_week' => $day, 'opens_at' => '16:00', 'closes_at' => '20:00']);
        }
        $business->schedules()->create(['day_of_week' => 6, 'opens_at' => '09:00', 'closes_at' => '15:00']);

        $services = $business->services()->pluck('id', 'name');
        foreach ([
            ['Diego Ramírez', 'Barbero', 'teal', $services->values()->all(), []],
            ['Sofía Herrera', 'Barbera y colorista', 'violet', $services->except('Afeitado clásico')->values()->all(), []],
            // Works afternoons and Saturdays, with hours of his own.
            ['Luis Ortega', 'Barbero', 'amber', $services->only(['Corte de cabello', 'Corte y barba', 'Corte infantil'])->values()->all(), [[3, '16:00', '20:00'], [4, '16:00', '20:00'], [5, '16:00', '20:00'], [6, '09:00', '15:00']]],
        ] as [$name, $title, $color, $serviceIds, $hours]) {
            $staffMember = $business->staffMembers()->create(['name' => $name, 'title' => $title, 'color' => $color]);
            $staffMember->services()->attach($serviceIds);

            foreach ($hours as [$day, $opensAt, $closesAt]) {
                $staffMember->schedules()->create(['business_id' => $business->id, 'day_of_week' => $day, 'opens_at' => $opensAt, 'closes_at' => $closesAt]);
            }
        }

        $business->blockedDates()->create([
            'date' => $business->now()->addDays(12)->toDateString(),
            'reason' => 'Capacitación del equipo',
        ]);

        $business->update([
            'logo_path' => Storage::disk('public')->putFile("businesses/{$business->id}", new File(__DIR__.'/images/logo.png')),
            'cover_path' => Storage::disk('public')->putFile("businesses/{$business->id}", new File(__DIR__.'/images/cover.jpg')),
        ]);

        DB::transaction(fn () => $this->seedAppointments($business, $client));
    }

    /**
     * Fill each barber's slots from eleven months ago to ten days ahead.
     * Older months are quieter, so the reports show a business that is growing.
     */
    private function seedAppointments(Business $business, User $client): void
    {
        $staff = $business->staffMembers()->with('services')->get()->map(fn (StaffMember $staffMember) => [
            'member' => $staffMember,
            'services' => $staffMember->services,
            'schedules' => $staffMember->workingSchedules()->groupBy('day_of_week'),
        ]);
        $now = $business->now();
        $clientBookings = 0;

        $firstDay = $now->startOfMonth()->subMonths(11);

        for ($offset = (int) $firstDay->diffInDays($now->startOfDay()) * -1; $offset <= 10; $offset++) {
            $day = $now->startOfDay()->addDays($offset);
            $fillRate = match (true) {
                $offset < -7 => 15 + (int) round(30 * (1 - abs($offset) / 335)),
                $offset < 0 => 55,
                default => 30,
            };

            if ($business->blockedDates()->whereDate('date', $day->toDateString())->exists()) {
                continue;
            }

            foreach ($staff as ['member' => $staffMember, 'services' => $services, 'schedules' => $schedules]) {
                foreach ($schedules->get($day->dayOfWeek, []) as $schedule) {
                    $cursor = $day->setTimeFromTimeString($schedule->opensAtTime());
                    $closesAt = $day->setTimeFromTimeString($schedule->closesAtTime());

                    while ($cursor->lt($closesAt)) {
                        /** @var Service $service */
                        $service = $services->random();
                        $end = $cursor->addMinutes($service->duration_minutes);

                        if ($end->gt($closesAt) || random_int(1, 100) > $fillRate) {
                            $cursor = $cursor->addMinutes(30);

                            continue;
                        }

                        $isClient = $offset > 0 && $clientBookings < 2 && random_int(1, 6) === 1;
                        $this->createAppointment($business, $service, $staffMember, $cursor, $now, $isClient ? $client : null);
                        $clientBookings += $isClient ? 1 : 0;
                        $cursor = $end;
                    }
                }
            }
        }
    }

    private function createAppointment(Business $business, Service $service, StaffMember $staffMember, CarbonImmutable $start, CarbonImmutable $now, ?User $client): void
    {
        $name = $client?->name ?? self::CUSTOMERS[array_rand(self::CUSTOMERS)];
        $status = $this->randomStatus($start->lt($now));
        $bookedAt = $start->subDays(random_int(2, 6))->min($now->subHours(random_int(1, 48)));
        $cancelledAt = $start->subHours(3)->min($now->subMinutes(30))->max($bookedAt->addHour());

        $appointment = $business->appointments()->create([
            'service_id' => $service->id,
            'staff_member_id' => $staffMember->id,
            'user_id' => $client?->id,
            'customer_name' => $name,
            'customer_email' => $client?->email ?? str($name)->ascii()->lower()->replace(' ', '.')->append('@example.com')->toString(),
            'customer_phone' => '55 '.random_int(1000, 9999).' '.random_int(1000, 9999),
            'whatsapp_opt_in' => random_int(1, 3) === 1,
            'notes' => random_int(1, 8) === 1 ? 'Prefiero que me atienda el mismo barbero de la última vez.' : null,
            'starts_at' => $start->utc(),
            'ends_at' => $start->addMinutes($service->duration_minutes)->utc(),
            'status' => $status,
            'price_cents' => $service->price_cents,
            'confirmed_at' => $status === AppointmentStatus::Pending ? null : $bookedAt->addMinutes(20)->utc(),
            'cancelled_at' => $status === AppointmentStatus::Cancelled ? $cancelledAt->utc() : null,
            'cancelled_by' => $status === AppointmentStatus::Cancelled ? CancelledBy::Customer : null,
        ]);

        $appointment->forceFill(['created_at' => $bookedAt->utc(), 'updated_at' => $bookedAt->utc()])->saveQuietly();
    }

    private function randomStatus(bool $isPast): AppointmentStatus
    {
        $roll = random_int(1, 100);

        if ($isPast) {
            return match (true) {
                $roll <= 82 => AppointmentStatus::Completed,
                $roll <= 90 => AppointmentStatus::NoShow,
                default => AppointmentStatus::Cancelled,
            };
        }

        return match (true) {
            $roll <= 78 => AppointmentStatus::Confirmed,
            $roll <= 90 => AppointmentStatus::Pending,
            default => AppointmentStatus::Cancelled,
        };
    }
}
