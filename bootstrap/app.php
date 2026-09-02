<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified.code' => EnsureEmailIsVerified::class,
        ]);

        // route('login') needs the {locale} parameter; Filament/Livewire requests
        // run outside the locale group where URL::defaults is not set.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin*') || $request->is('livewire*')) {
                return route('filament.admin.auth.login');
            }

            return route('login', ['locale' => session('locale', config('app.locale'))]);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
