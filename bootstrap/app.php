<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('throttle:240,1')
                ->prefix('webhooks/whatsapp')
                ->name('whatsapp.webhook.')
                ->group(base_path('routes/whatsapp.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The app sits behind a reverse proxy (nginx/Cloudflare) at smm.bitflux.com.np.
        // Trust the standard X-Forwarded-* headers from it so Laravel resolves the real
        // client IP/scheme instead of the proxy's — required for correct HTTPS URLs,
        // secure cookies, and the WhatsApp webhook's IP in logs.
        //
        // TRUSTED_PROXIES defaults to "*" (trust whatever is in front of the app, which
        // is the normal setup when the server itself isn't directly internet-facing).
        // Set it to the proxy's actual IP(s)/CIDR in .env if that assumption doesn't hold.
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', '*'));

        $middleware->trustProxies(
            at: $trustedProxies === '*' ? '*' : array_filter(array_map('trim', explode(',', $trustedProxies))),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
