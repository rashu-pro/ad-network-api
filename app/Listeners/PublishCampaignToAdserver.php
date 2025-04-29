<?php

namespace App\Listeners;

use App\Enums\PublisherCampaignStatus;
use App\Events\CampaignPublished;
use App\Facades\AdServer;
use App\Models\CampaignMapping;
use App\Models\Zone;
use App\Services\AdServerService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublishCampaignToAdserver
{
    private $adServerService;

    public function __construct()
    {
        $this->adServerService = new AdServerService(
            env('AD_SERVER_BASE_URL'),
            env('AD_SERVER_SUPER_ADMIN_USERNAME'),
            env('AD_SERVER_SUPER_ADMIN_PASSWORD')
        );
    }

    public function handle(CampaignPublished $event): void
    {
        $campaign = $event->campaign;
        $campaignMappings = CampaignMapping::where('campaign_id', $campaign->id)->get();

        foreach ($campaignMappings as $campaignMapping) {
            if ($campaignMapping->is_active) {
                continue;
            }

            $zone = Zone::findOrFail($campaignMapping->publisher_zone_id);

            if ($campaignMapping->status === PublisherCampaignStatus::APPROVE) {
                // Create Zone
                $zoneAdserverId = AdServer::createZone(
                    (int)$campaignMapping->publisherAsset->publisher_adserver_id,
                    $zone->zone_name . '_' . now(),
                    $zone->width,
                    $zone->height
                );

                $campaignMapping->update(['publisher_zone_adserver_id' => $zoneAdserverId]);
                $campaignMapping->refresh();

                // Create Campaign
                $campaignAdserverId = AdServer::createCampaign(
                    (int)$campaign->advertiser_adserver_id,
                    $campaign->campaign_name . '_' . now(),
                    $campaign->mappings()->first()->start_date,
                    $campaign->mappings()->first()->end_date,
                );

                $campaignMapping->update(['campaign_adserver_id' => $campaignAdserverId]);
                $campaignMapping->refresh();

                if (!$campaignMapping->hasMedia('banner')) {
                    Log::error('Campaign does not have a banner for zone ' . $zone->zone_name);
                    return;
                }

                $banner = $campaignMapping->getFirstMedia('banner');

                // Upload Banner
                $bannerAdserverId = AdServer::uploadBanner(
                    (int)$campaignMapping->campaign_adserver_id,
                    $banner->name,
                    $banner->getUrl(),
                    $campaign->target_url,
                    $campaignMapping->publisherZone->width,
                    $campaignMapping->publisherZone->height
                );

                $campaignMapping->update(['banner_adserver_id' => $bannerAdserverId]);
                $campaignMapping->refresh();

                // Link Zone to Campaign
                $isActive = AdServer::linkZoneToCampaign(
                    (int)$campaignMapping->publisher_zone_adserver_id,
                    (int)$campaignMapping->campaign_adserver_id
                );

                $campaignMapping->update(['is_active' => $isActive]);
                $campaignMapping->refresh();

                if (!$campaignMapping->is_active) {
                    throw new HttpException(500, "Campaign already published!");
                }
            }
        }
    }
}
