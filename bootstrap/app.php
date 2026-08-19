<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        // No web routes: this service is an API and the reader app is deployed
        // separately. Loading the `web` group would put sessions and encrypted
        // cookies in front of every page — which then hard-fails without an
        // APP_KEY, so the root URL returned a 500 while the API itself was fine.
        //
        // Registering the index here instead keeps it stateless, like the rest
        // of the service.
        then: function () {
            Route::get('/', fn () => response()->json([
                'name' => config('app.name'),
                'status' => 'ok',
                'api' => url('/api'),
                'health' => url('/up'),
                'locales' => array_keys(config('himam.locales')),
            ]));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Every API response is language-negotiated, so the locale has to be
        // resolved before any controller reads a translatable attribute.
        $middleware->api(prepend: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // Managed hosts terminate TLS at their edge and forward the request over
        // plain HTTP. Without trusting the forwarding headers Laravel builds
        // http:// URLs — which the browser then blocks as mixed content on an
        // https page, and which would break the certificate verification links.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
