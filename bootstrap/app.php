<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hosts like Render sit behind a proxy; set TRUSTED_PROXIES=* there so
        // HTTPS links, signed URLs and per-visitor rate limits use the real client details.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));

        $middleware->alias(['admin' => EnsureUserIsAdmin::class]);
        $middleware->redirectUsersTo(fn ($request) => $request->user()->homeRoute());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
