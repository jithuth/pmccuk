<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'api/telegram/webhook',
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\SharedHostingCompatibility::class);
        $middleware->append(\App\Http\Middleware\WebApplicationFirewall::class);
        $middleware->append(\App\Http\Middleware\EnsureMfaCompleted::class);
        $middleware->append(\App\Http\Middleware\EnforceSessionSecurity::class);
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('member/*') || $request->is('member')) {
                return route('member.login');
            }
            return route('admin.login');
        });
        $middleware->alias([
            'admin.role' => \App\Http\Middleware\AdminRole::class,
            'mfa.check' => \App\Http\Middleware\EnsureMfaCompleted::class,
            'session.secure' => \App\Http\Middleware\EnforceSessionSecurity::class,
            'admin.mfa' => \App\Http\Middleware\EnforceMandatoryAdminMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
