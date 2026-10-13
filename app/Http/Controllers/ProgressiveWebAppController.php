<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets ReservaPro be installed on a phone or desktop as an app (PWA).
 */
class ProgressiveWebAppController extends Controller
{
    public const THEME_COLOR = '#0d9488';

    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => 'ReservaPro',
            'short_name' => 'ReservaPro',
            'description' => 'Agenda y reservaciones en línea para tu negocio.',
            'lang' => 'es',
            'id' => '/',
            'start_url' => route('app.open', absolute: false),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'theme_color' => self::THEME_COLOR,
            'background_color' => '#f8fafc',
            'categories' => ['business', 'productivity'],
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => '/icons/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
            ],
            'shortcuts' => [
                ['name' => 'Agenda', 'url' => route('admin.agenda', absolute: false), 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
                ['name' => 'Mis citas', 'url' => route('my-appointments', absolute: false), 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ],
        ], headers: ['Content-Type' => 'application/manifest+json'], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Where the installed app opens: the owner's agenda, the customer's appointments, or the login page.
     */
    public function open(Request $request): RedirectResponse
    {
        return $request->user()
            ? redirect($request->user()->homeRoute())
            : redirect()->route('login');
    }
}
