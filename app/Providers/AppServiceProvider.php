<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsAdmin;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        // Re-check the admin middleware on every Livewire request, not just the first page load.
        Livewire::addPersistentMiddleware([EnsureUserIsAdmin::class]);

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Restablece tu contraseña de ReservaPro')
                ->greeting("Hola, {$notifiable->name}")
                ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
                ->action('Elegir una contraseña nueva', route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]))
                ->line("El enlace vence en {$minutes} minutos. Si no lo pediste tú, ignora este correo: tu contraseña no cambia.");
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });
    }
}
