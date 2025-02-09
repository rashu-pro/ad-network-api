<?php

namespace App\Listeners;

use App\Events\SendCampaignCodesToPublishers;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendCampaignCodesToPublishersListener
{
//    public $connection = 'database';
//    public $queue = 'adserver';

    /**
     * Handle the event.
     */
    public function handle(SendCampaignCodesToPublishers $event)
    {
        $groupedMappings = $event->campaignMappings->groupBy('publisher_asset_id');

        foreach ($groupedMappings as $publisherAssetId => $mappings) {
            if($mappings->first()->publisherAsset->asset->type != 'online'){
                continue;
            }
            $targetUrl = $mappings->first()->publisherAsset->url ?? null;

            if (!$targetUrl) {
                Log::warning("No target URL found for publisher_asset_id: {$publisherAssetId}");
                continue;
            }

            $codes = $mappings->pluck('code')->toArray();

            try {
                $payload = [
                    'publisher_asset_id' => $publisherAssetId,
                    'zone_scripts' => $codes,
                ];

                // Send codes to the publisher's target URL
                $response = Http::post("{$targetUrl}/wp-json/adserver/v1/zone-scripts/web", $payload);

                if ($response->failed()) {
                    Log::error("Failed to send campaign codes", [
                        'publisher_asset_id' => $publisherAssetId,
                        'payload' => $payload,
                        'target_url' => $targetUrl,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
                Log::info('Send campaign codes to ' . $targetUrl.' for '.$publisherAssetId,$response->json());
            } catch (\Exception $e) {
                Log::error("Error sending campaign codes", [
                    'publisher_asset_id' => $publisherAssetId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
