<?php

namespace App\Listeners;

use App\Events\PublishCampaignMappingToAdServer;
use App\Events\SendCampaignCodesToPublishers;
use App\Facades\AdServer;
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

            // Fetch campaign code from AdServer
            $mapping->code = AdServer::getCampaignEmbedsByAdZone($mapping->publisher_zone_adserver_id);
            $mapping->save();

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
