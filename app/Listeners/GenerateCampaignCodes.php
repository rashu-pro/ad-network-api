<?php

namespace App\Listeners;

use App\Events\CampaignPublished;
use App\Events\SendCampaignCodesToPublishers;
use App\Facades\AdServer;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateCampaignCodes implements ShouldDispatchAfterCommit
{
//    public $connection = 'database';
//    public $queue = 'adserver';

    /**
     * Handle the event.
     */
    public function handle(CampaignPublished $event): void
    {
        $mappings = $event->campaign->mappings()->get();

        DB::beginTransaction();

        try {
            foreach ($mappings as $mapping) {
                if (!$mapping->is_active) {
                    continue;
                }

                // Fetch campaign code from AdServer
                $mapping->code = AdServer::getCampaignEmbedsByAdZone($mapping->publisher_zone_adserver_id);
                $mapping->save();
            }

            DB::commit();
            SendCampaignCodesToPublishers::dispatch($event->campaign->mappings()->get());
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to generate campaign codes", [
                'campaign_id' => $event->campaign->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to allow retrying if using queue
        }
    }
}
