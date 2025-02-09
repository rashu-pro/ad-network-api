<?php

namespace App\Listeners;

use App\Events\SendCampaignCodesToPublishers;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendCampaignCodesToPublishersListener implements ShouldQueue
{
    public $connection = 'redis';
    public $queue = 'adserver';

    /**
     * Handle the event.
     */
    public function handle(SendCampaignCodesToPublishers $event)
    {
        $groupedMappings = $event->campaignMappings->groupBy('publisher_asset_id');

        foreach ($groupedMappings as $publisherAssetId => $mappings) {
            $targetUrl = $mappings->first()->publisherAsset->target_url ?? null;

            if (!$targetUrl) {
                Log::warning("No target URL found for publisher_asset_id: {$publisherAssetId}");
                continue;
            }

            $codes = $mappings->pluck('code')->toArray();

            try {
                $payload = [
                    'publisher_asset_id' => $publisherAssetId,
                    'campaign_codes' => $codes,
                ];

                // Send codes to the publisher's target URL
                $response = Http::post("{$targetUrl}/ms-network-campaign-callback", $payload);

                if ($response->failed()) {
                    Log::error("Failed to send campaign codes", [
                        'publisher_asset_id' => $publisherAssetId,
                        'payload' => $payload,
                        'target_url' => $targetUrl,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
                Log::info('Send campaign codes to ' . $targetUrl.' for '.$publisherAssetId);
            } catch (\Exception $e) {
                Log::error("Error sending campaign codes", [
                    'publisher_asset_id' => $publisherAssetId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
