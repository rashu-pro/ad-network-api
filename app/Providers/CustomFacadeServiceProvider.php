<?php

namespace App\Providers;

use App\Services\AdServerService;
use App\Services\SecureApiService;
use Illuminate\Support\ServiceProvider;

class CustomFacadeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('secure_api', function ($app) {
            return new SecureApiService(env('SECURE_API_BASE_URL','https://api-mosque-community.secure-api.dev'));
        });
        $this->app->singleton('adserver', function ($app) {
            return new AdServerService(env('AD_SERVER_BASE_URL'),env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'));
        });
    }
}
