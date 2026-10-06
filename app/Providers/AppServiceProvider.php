<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // Behind a reverse proxy (see bootstrap/app.php trustProxies), Laravel already
        // knows the original scheme from X-Forwarded-Proto. This is a belt-and-braces
        // force for generated URLs (Filament assets, webhook links, etc.) in production.
        if (config('app.env') === 'production' || filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }
    }
}
