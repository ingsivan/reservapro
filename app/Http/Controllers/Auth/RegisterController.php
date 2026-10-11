<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BusinessSetup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, BusinessSetup $setup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'account_type' => ['required', Rule::enum(Role::class)],
            'business_name' => ['nullable', 'required_if:account_type,admin', 'string', 'max:80'],
        ]);

        $user = DB::transaction(function () use ($validated, $setup) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['account_type'],
            ]);

            if ($user->isAdmin()) {
                $setup->createFor($user, $validated['business_name']);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->homeRoute())->with('status', $user->isAdmin()
            ? '¡Listo! Tu negocio ya tiene un horario de ejemplo. Agrega tu primer servicio para empezar a recibir reservas.'
            : '¡Bienvenido! Aquí verás las citas que reserves.');
    }
}
