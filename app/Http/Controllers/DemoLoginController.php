<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Entrar como admin demo": signs visitors into the seeded demo business.
 */
class DemoLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $demo = User::where('email', DatabaseSeeder::DEMO_ADMIN_EMAIL)->first();

        if ($demo === null) {
            return back()->with('error', 'La cuenta demo no está disponible. Ejecuta php artisan db:seed para crearla.');
        }

        Auth::login($demo);
        $request->session()->regenerate();

        return to_route('admin.agenda');
    }
}
