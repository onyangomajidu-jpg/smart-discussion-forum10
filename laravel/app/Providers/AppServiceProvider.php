<?php

namespace App\Providers;

use App\Contracts\IAssessment;
use App\Contracts\IAuthentication;
use App\Contracts\IContentManagement;
use App\Services\AssessmentService;
use App\Services\AuthenticationService;
use App\Services\ContentManagementService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IAuthentication::class, AuthenticationService::class);
        $this->app->bind(IContentManagement::class, ContentManagementService::class);
        $this->app->bind(IAssessment::class, AssessmentService::class);
    }

    public function boot(): void {}
}
