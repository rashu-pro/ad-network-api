<?php

namespace App\Listeners;

use App\Enums\PublisherCampaignStatus;
use App\Events\PublishCampaignMappingToAdServer;
use App\Facades\AdServer;
use App\Models\Zone;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CampaignMappingPublishListener
{
    public function __construct()
    {
        //
    }

    public function handle(PublishCampaignMappingToAdServer $event): void
    {
        $campaignMapping = $event->mapping;

        if ($campaignMapping->is_active) {
            return;
        }

        $zone = Zone::findOrFail($campaignMapping->publisher_zone_id);

        if ($campaignMapping->status == PublisherCampaignStatus::APPROVE) {
            try {
                // Step 1: Create Zone on AdServer
                $zoneAdServerId = AdServer::createZone(
                    (int) $campaignMapping->publisherAsset->publisher_adserver_id,
                    $zone->zone_name . '_' . now(),
                    $zone->width,
                    $zone->height
                );

                $campaignMapping->update([
                    'publisher_zone_adserver_id' => $zoneAdServerId,
                ]);
                $campaignMapping->refresh();

                // Step 2: Create Campaign on AdServer
                $campaignAdServerId = AdServer::createCampaign(
                    (int) $campaignMapping->campaign->advertiser_adserver_id,
                    $campaignMapping->campaign->campaign_name . '_' . now(),
                    $campaignMapping->start_date,
                    $campaignMapping->end_date
                );

                $campaignMapping->update([
                    'campaign_adserver_id' => $campaignAdServerId,
                ]);
                $campaignMapping->refresh();

                // Step 3: Upload Banner to AdServer
                if (!$campaignMapping->hasMedia('banner')) {
                    Log::error('Campaign does not have a banner for zone ' . $zone->zone_name);
                    return;
                }

                $banner = $campaignMapping->getFirstMedia('banner');

                $bannerAdServerId = AdServer::uploadBanner(
                    (int) $campaignAdServerId,
                    $banner->name,
                    $banner->getUrl(),
                    $campaignMapping->campaign->target_url,
                    $campaignMapping->publisherZone->width,
                    $campaignMapping->publisherZone->height
                );

                $campaignMapping->update([
                    'banner_adserver_id' => $bannerAdServerId,
                ]);
                $campaignMapping->refresh();

                // Step 4: Link Zone to Campaign
                $success = AdServer::linkZoneToCampaign(
                    (int) $zoneAdServerId,
                    (int) $campaignAdServerId
                );

                $campaignMapping->update([
                    'notes' => null,
                    'is_active' => $success,
                ]);


                if (!$campaignMapping->is_active) {
                    throw new HttpException(500, "Campaign was unable to publish. Try again later.");
                }
                $campaignMapping->refresh();

                $latestPause = $campaignMapping->pauseHistories()
                    ->whereNull('resumed_at')
                    ->latest()
                    ->first();

                if ($latestPause) {
                    $latestPause->update([
                        'resumed_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to publish campaign mapping to AdServer: ' . $e->getMessage(), [
                    'campaign_mapping_id' => $campaignMapping->id
                ]);
                throw new HttpException(500, "Failed to publish campaign mapping. Please try again later.");
            }
        }
    }
}
