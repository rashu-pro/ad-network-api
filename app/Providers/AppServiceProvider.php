<?php

namespace App\Providers;

use App\Events\CampaignPublished;
use App\Events\PublishCampaignMappingToAdServer;
use App\Listeners\CampaignMappingGenerateCode;
use App\Listeners\CampaignMappingPublishListener;
use App\Listeners\GenerateCampaignCodes;
use App\Listeners\PublishCampaignToAdserver;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Policies\CampaignMappingPolicy;
use App\Policies\CampaignPolicy;
use App\Repositories\Eloquent\AssetRepository;
use App\Repositories\Eloquent\AssetValuationRepository;
use App\Repositories\Eloquent\CampaignMappingRepository;
use App\Repositories\Eloquent\CampaignRepository;
use App\Repositories\Eloquent\ZoneRepository;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use App\Repositories\Interfaces\AssetValuationRepositoryInterface;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\ZoneRepositoryInterface;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AssetRepositoryInterface::class, AssetRepository::class);
        $this->app->bind(AssetValuationRepositoryInterface::class, AssetValuationRepository::class);
        $this->app->bind(ZoneRepositoryInterface::class, ZoneRepository::class);
        $this->app->bind(CampaignRepositoryInterface::class, CampaignRepository::class);
        $this->app->bind(CampaignMappingRepositoryInterface::class, CampaignMappingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(CampaignMapping::class, CampaignMappingPolicy::class);
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
        Event::listen(
            CampaignPublished::class,
            PublishCampaignToAdserver::class
        );
        Event::listen(
            CampaignPublished::class,
            GenerateCampaignCodes::class
        );
        Event::listen(
            PublishCampaignMappingToAdServer::class,
            CampaignMappingPublishListener::class
        );
        Event::listen(
            PublishCampaignMappingToAdServer::class,
            CampaignMappingGenerateCode::class
        );
    }
}
