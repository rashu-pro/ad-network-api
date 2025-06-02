<?php

namespace App\Listeners;

use App\Enums\PublisherCampaignStatus;

use App\Events\SendCampaignCodesToPublishers;

use App\Models\CampaignMapping;

use App\Models\PublisherAsset;

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

        // Get unique publisher_asset_ids from the event's mappings

        $publisherAssetIds = $event->campaignMappings->pluck('publisher_asset_id')->unique();

        foreach ($publisherAssetIds as $publisherAssetId) {

            // Retrieve all mappings for this publisher_asset_id from the database

            $mappings = CampaignMapping::where('publisher_asset_id', $publisherAssetId)->whereNotNull('code')->where('status', PublisherCampaignStatus::APPROVE->value)->get();

            $asset = PublisherAsset::find($publisherAssetId);

            if ($mappings->isEmpty()) {

                Log::warning("No campaign mappings found for publisher_asset_id: {$publisherAssetId}");

            }

            // Ensure it's an online asset before proceeding

            if ($asset->asset->type != 'online') {

                continue;

            }

            $targetUrl = $asset->url ?? null;

            if (!$targetUrl) {

                Log::warning("No target URL found for publisher_asset_id: {$publisherAssetId}");

                continue;

            }

            // Get unique codes from all mappings for this publisher_asset_id

            $codes = count($mappings) > 0 ? $mappings->pluck('code')->unique()->values()->toArray() : [];

            try {

                $payload = [

                    'publisher_asset_id' => $publisherAssetId,

                    'zone_scripts' => $codes,

                ];

                $publisherAsset = $mappings->first()->publisherAsset;
                if($publisherAsset){
                    // Send data to the publisher's webhook URL
                    $response = Http::withHeaders([
                        'User-Agent' => 'MyCustomUserAgent/1.0',
                        'Accept' => 'application/json',
                        'Referer' => url()->current(),
                    ])->post("{$publisherAsset->webhook_path}", $payload);
                }



                Log::info("Webhook Response for {$publisherAssetId} {$response->status()}: {$response->body()}");

                if (!$response->ok()) {

                    Log::error("Failed to send campaign codes for {$publisherAssetId}: {$response->body()}", [

                        'publisher_asset_id' => $publisherAssetId,

                        'payload' => $payload,

                        'target_url' => $targetUrl,

                        'status' => $response->status(),

                        'body' => $response->body(),

                    ]);

                }

            } catch (\Exception $e) {

                Log::error("Error sending campaign codes", [

                    'publisher_asset_id' => $publisherAssetId,

                    'error' => $e->getMessage(),

                ]);

            }

        }

    }

}

