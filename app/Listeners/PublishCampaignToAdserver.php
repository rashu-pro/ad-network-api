<?php

namespace App\Listeners;

use App\Enums\PublisherCampaignStatus;
use App\Events\CampaignPublished;
use App\Models\CampaignMapping;
use App\Models\Zone;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublishCampaignToAdserver
{
//    public $connection = 'database';
//    public $queue = 'adserver';

    /**
     * Handle the event.
     */
    public function handle(CampaignPublished $event): void
    {
        $campaign = $event->campaign;
        $campaignMappings = CampaignMapping::where('campaign_id',$campaign->id)
            ->get();
        foreach($campaignMappings as $campaignMapping){
            if($campaignMapping->is_active){
                continue;
            }
            $zone = Zone::findOrFail($campaignMapping->publisher_zone_id);
            if($campaignMapping->status == PublisherCampaignStatus::APPROVE){
                // Payload data
                $payload = [
                    'publisherId' => (int)$campaignMapping->publisherAsset->publisher_adserver_id,
                    'zoneName' => $zone->zone_name.'_'.now(),
                    'type' => 0,
                    'width' => $zone->width,
                    'height' => $zone->height,
                ];
                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/zon/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
                $zone_adserver_id = $response->object()->zoneId;

                $campaignMapping->update([
                    'publisher_zone_adserver_id' => $zone_adserver_id,
                ]);
                $campaignMapping->refresh();
                // Payload data
                $payload = [
                    'advertiserId' => (int)$campaign->advertiser_adserver_id,
                    'campaignName' => $campaign->campaign_name.'_'.now(),
                    'startDate' => $campaign->start_date,
                    'endDate' => $campaign->end_date,
                    'impressions' => 10000,
                    'revenueType' => 1,
                    'revenue' => 12.50,
                    'weight' => 1
                ];

                Log::info('From publish campaign to payload', $payload);
                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/cam/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);

                Log::info('From publish campaign to adserver zone reponse', $response->json());
                $campaign_adserver_id = $response->object()->campaignId;
                $campaignMapping->update([
                    'campaign_adserver_id' => $campaign_adserver_id,
                ]);
                $campaignMapping->refresh();


                if(!$campaignMapping->hasMedia('banner')){
                    Log::error('Campaign does not have banner for zone '.$zone->name);
                    return ;
                }
                $banner = $campaignMapping->getFirstMedia('banner');

                // Payload data
                $payload = [
                    'campaignId' => (int)$campaignMapping->campaign_adserver_id,
                    'bannerName' => $banner->name,
                    'storageType' => "url",
                    'imageURL' => $banner->getUrl(),
                    'url' => $campaign->target_url,
                    'width' => $campaignMapping->publisherZone->width,
                    'height' => $campaignMapping->publisherZone->height
                ];

                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/bnn/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);

                $banner_adserver_id = $response->object()->bannerId;
                $campaignMapping->update([
                    'banner_adserver_id' => $banner_adserver_id,
                ]);

                $campaignMapping->refresh();

                $endpoint = env('AD_SERVER_BASE_URL').'/zon/'.$campaignMapping->publisher_zone_adserver_id.'/cam/'.$campaignMapping->campaign_adserver_id;
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint);
                $isActive = $response->body() === '{"OK"}';
                $campaignMapping->update([
                    'is_active' => $isActive,
                ]);
                $campaignMapping->refresh();
                if(!$campaignMapping->is_active){
                    throw new HttpException("Campaign was unable to publish. Try again later.");
                }
            }
        }
    }
}
