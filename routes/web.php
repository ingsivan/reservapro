<?php

use App\Http\Controllers\Admin\ExportAppointmentsController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManageAppointmentController;
use App\Livewire\Admin\Agenda;
use App\Livewire\Admin\BusinessSettings;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\ScheduleManager;
use App\Livewire\Admin\Services;
use App\Livewire\Admin\Team;
use App\Livewire\BookingWizard;
use App\Livewire\MyAppointments;
use App\Livewire\RescheduleAppointment;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::livewire('/reservar/{business}', BookingWizard::class)->name('booking');

Route::middleware('signed')->group(function () {
    Route::get('/cita/{appointment}', [ManageAppointmentController::class, 'show'])->name('appointments.manage');
    Route::get('/cita/{appointment}/calendario.ics', [ManageAppointmentController::class, 'calendar'])->name('appointments.calendar');
    Route::livewire('/cita/{appointment}/reprogramar', RescheduleAppointment::class)->name('appointments.reschedule');
    Route::post('/cita/{appointment}/cancelar', [ManageAppointmentController::class, 'cancel'])->name('appointments.cancel');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:auth');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:auth');
    Route::get('/olvide-mi-contrasena', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/olvide-mi-contrasena', [ForgotPasswordController::class, 'store'])->middleware('throttle:auth')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/restablecer-contrasena', [ResetPasswordController::class, 'store'])->middleware('throttle:auth')->name('password.update');
    Route::post('/demo', DemoLoginController::class)->middleware('throttle:auth')->name('demo.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::livewire('/mis-citas', MyAppointments::class)->name('my-appointments');

    Route::middleware('admin')->prefix('panel')->name('admin.')->group(function () {
        Route::livewire('/', Agenda::class)->name('agenda');
        Route::livewire('/servicios', Services::class)->name('services');
        Route::livewire('/equipo', Team::class)->name('team');
        Route::livewire('/horario', ScheduleManager::class)->name('schedule');
        Route::livewire('/reportes', Reports::class)->name('reports');
        Route::get('/reportes/citas.csv', ExportAppointmentsController::class)->name('reports.export');
        Route::livewire('/ajustes', BusinessSettings::class)->name('settings');
    });
});
