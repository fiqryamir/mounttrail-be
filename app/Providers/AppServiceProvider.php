<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- Add this line
use Illuminate\Support\Facades\Request;

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
    public function boot()
    {
        // --- ADD THE FOLLOWING CODE ---

        // Check if the application is running in a production environment
        // and force the URL to use HTTPS.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            // Set the trusted proxies to trust all proxies,
            // which is fine for a platform like Render.
            Request::setTrustedProxies(
                ['*'],
                // We explicitly list the headers instead of using the 'ALL' shortcut
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_TLS
            );
        }

        // --- END OF ADDED CODE ---
    }
}
