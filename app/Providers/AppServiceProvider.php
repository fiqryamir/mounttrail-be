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
        // On Render, the app is always in a 'production' like environment
        if ($this->app->environment('production')) {
            // Force Laravel to always generate URLs with https
            URL::forceScheme('https');

            // Tell Laravel to trust the headers from the proxy
            // Using only the most common headers that exist in older Laravel versions
            \Illuminate\Http\Request::setTrustedProxies(
                ['*'], // Trust all proxies
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            );
        }
    }
}
