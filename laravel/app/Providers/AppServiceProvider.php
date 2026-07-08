<?php

namespace App\Providers;

use App\Contracts\IAssessment;
use App\Contracts\IAuthentication;
use App\Services\AssessmentService;
use App\Services\AuthenticationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IAuthentication::class, AuthenticationService::class);
        $this->app->bind(IAssessment::class,     AssessmentService::class);
    }

    public function boot(): void {}
}
