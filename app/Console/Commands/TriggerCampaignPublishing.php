<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\CampaignMapping;
use App\Events\CampaignPublished;
use App\Enums\PublisherCampaignStatus;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TriggerCampaignPublishing extends Command
{
    protected $signature = 'campaign:check-start';
    protected $description = 'Trigger CampaignPublished events for campaigns with mappings starting today';

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();

        // Step 1: Get today's mappings
        $mappings = CampaignMapping::with('campaign')
            ->whereDate('start_date', $today)
            ->where('status', PublisherCampaignStatus::APPROVE)
            ->where('is_active', false)
            ->get();

        // Step 2: Group by unique campaign_id
        $uniqueCampaigns = $mappings->groupBy('campaign_id')->map(function (Collection $group) {
            return $group->first()->campaign;
        });

        // Step 3: Dispatch event once per campaign
        foreach ($uniqueCampaigns as $campaign) {
            event(new CampaignPublished($campaign));
            $this->info("CampaignPublished event triggered for Campaign ID: {$campaign->id}");
        }
    }
}
