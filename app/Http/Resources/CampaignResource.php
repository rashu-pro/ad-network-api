<?php

namespace App\Http\Resources;

use App\Facades\SecureApi;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\BillingService;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $billingService = new BillingService(); // use existing methods
        $campaign = Campaign::findOrFail($this->id);
        return [
            'campaign' => [
                'id' => $this->id,
                'name' => $this->campaign_name,
                'advertiser_adserver_id' => $this->advertiser_adserver_id,
                'status' => $this->status,
                'is_draft' => $this->is_draft,
                'bill_till_now' => $billingService->calculateCampaignBillTillNow($campaign),
                'total_bill' => $billingService->calculateTotalCampaignBill($campaign),
                'advertiser' => $this->advertiserDetails($this->advertiser) ?? null
            ],
            'publishers' => $this->campaignMappings()->get()->groupBy('publisher_id')->map(function ($groupedMappings) use ($billingService) {
                $firstMapping = $groupedMappings->first();
                $publisher = $firstMapping->publisher;
                $securePublisher = SecureApi::getUser($publisher->secure_api_id, $publisher->email);

                return [
                    'publisher_id' => $publisher->id,
                    'publisher_name' => $securePublisher['businessName'],
                    'publisher_address' => $securePublisher['businessInfo']['address'],
                    'logo' => $securePublisher['businessInfo']['logoUrl'],
                    'assets' => $groupedMappings->map(function ($mapping) use ($billingService) {
                        $asset = $mapping->publisherAsset;

                        return [
                            'id' => $asset->id,
                            'mapping_id' => $mapping->id,
                            'name' => $asset->asset->name,
                            'price_per_hour' => $asset->price_per_hour,
                            'calculated_price' => $mapping->calculated_price,
                            'start_date' => $mapping->start_date,
                            'end_date' => $mapping->end_date,
                            'zone_id' => $mapping->publisher_zone_id,
                            'zone_width' => $mapping->publisherZone->width,
                            'zone_height' => $mapping->publisherZone->height,
                            'zone_adserver_id' => $mapping->publisher_zone_adserver_id,
                            'campaign_adserver_id' => $mapping->campaign_adserver_id,
                            'url' => $mapping->publisherAsset->url,
                            'target_url' => $mapping->campaign->target_url,
                            'is_active' => $mapping->is_active,
                            'note' => $mapping->notes,
                            'banner' => $mapping->hasMedia('banner') ? $mapping->getFirstMedia('banner')->getUrl() : '#',
                            'status' => $mapping->status,
                            'notes' => $mapping->notes,
                            'bill' => $billingService->calculateCampaignMappingBill($mapping),
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(),
        ];
    }

    private function advertiserDetails(User $advertiser)
    {
        $secureAdvertiser = SecureApi::getUser($advertiser->secure_api_id, $advertiser->email);
        return [
            'name' => $secureAdvertiser['businessName'],
        ];
    }
}
