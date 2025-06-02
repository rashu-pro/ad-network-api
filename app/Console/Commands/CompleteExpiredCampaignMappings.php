<?php
namespace App\Console\Commands;

use App\Events\SendCampaignCodesToPublishers;
use App\Facades\AdServer;
use App\Models\CampaignMapping;
use App\Services\AdServerService;
use App\Enums\PublisherCampaignStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CompleteExpiredCampaignMappings extends Command
{
    protected $signature = 'campaign:expire-check';
    protected $description = 'Set expired campaign mappings to COMPLETED and stop them on the ad server';

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();

        // Get all mappings that have ended, are active, and not yet completed
        $expiredMappings = CampaignMapping::whereDate('end_date', '<=', $today)
            ->where('is_active', true)
            ->where('status', PublisherCampaignStatus::APPROVE) // Assuming APPROVE is before completion
            ->get();


        foreach ($expiredMappings as $mapping) {
            $campaignAdServerId = $mapping->campaign_adserver_id;

            try {
//                if ($campaignAdServerId) {
//                    AdServer::deleteCampaign($campaignAdServerId);
//                    $this->info("Deleted campaign ID {$campaignAdServerId} from AdServer.");
//                }

                $mapping->update([
                    'status' => PublisherCampaignStatus::COMPLETED->value,
                    'is_active' => false,
                    'code' => null
                ]);
                $mapping->refresh();
                event(new SendCampaignCodesToPublishers($mapping));
                $this->info("Updated mapping ID {$mapping->id} to COMPLETE.");
            } catch (\Exception $e) {
                $this->error("Error processing mapping ID {$mapping->id}: " . $e->getMessage());
            }
        }
    }
}
