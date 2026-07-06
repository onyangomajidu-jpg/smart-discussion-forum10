<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\IAuthentication;
use App\Contracts\IContentManagement;
use App\Services\AuthenticationService;
use App\Services\ContentManagementService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IAuthentication::class, AuthenticationService::class);
        $this->app->bind(IContentManagement::class, ContentManagementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
