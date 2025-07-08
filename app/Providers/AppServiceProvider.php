<?php

namespace App\Providers;

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
                Request::HEADER_X_FORWARDED_ALL
            );
        }

        // --- END OF ADDED CODE ---
    }
}
