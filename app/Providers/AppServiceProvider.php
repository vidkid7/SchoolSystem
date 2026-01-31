<?php

namespace App\Providers;

use App\Http\Services\SchoolService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind SchoolService to the container
        $this->app->singleton(SchoolService::class, function ($app) {
            return new SchoolService(/* any dependencies SchoolService may have */);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(125);

        // Ensure asset URLs use correct scheme and host (fixes CSS/JS not loading on Railway, proxies, etc.)
        if (!$this->app->runningInConsole() && request()->getSchemeAndHttpHost()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
            if (request()->secure()) {
                URL::forceScheme('https');
            }
        }
    }
}