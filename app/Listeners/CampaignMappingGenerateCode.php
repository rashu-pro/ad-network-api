<?php

namespace App\Listeners;

use App\Events\PublishCampaignMappingToAdServer;
use App\Events\SendCampaignCodesToPublishers;
use App\Facades\AdServer;
use App\Models\PublisherAsset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignMappingGenerateCode
{

    /**
     * Handle the event.
     */
    public function handle(PublishCampaignMappingToAdServer $event): void
    {
        $mapping = $event->mapping;
        DB::beginTransaction();

        try {
            if (!$mapping->is_active) {
                return;
            }

            $publisherAsset = PublisherAsset::find($mapping->publisher_asset_id);
            if ($publisherAsset->asset->assetType->name == 'Offline') {
                Log::warning("No Codes needs to be generated from adserver since the ad for offline asset (Campaignmappinggeneratecode): {$mapping->id}");
                return;
            }


            // Fetch campaign code from AdServer
            $mapping->code = AdServer::getCampaignEmbedsByAdZone($mapping->publisher_zone_adserver_id);
            $mapping->save();

            Log::info("zone code: {$mapping->code}");

            DB::commit();
            SendCampaignCodesToPublishers::dispatch($mapping->campaign->mappings()->get());
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to generate campaign codes", [
                'campaign_id' => $mapping->campaign->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to allow retrying if using queue
        }
    }
}
