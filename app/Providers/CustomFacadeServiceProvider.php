<?php

namespace App\Providers;

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
    }
}
