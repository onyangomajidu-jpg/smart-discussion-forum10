<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\IAuthentication;
use App\Services\AuthenticationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind IAuthentication interface to AuthenticationService
        $this->app->bind(IAuthentication::class, AuthenticationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
