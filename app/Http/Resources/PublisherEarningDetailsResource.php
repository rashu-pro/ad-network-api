<?php

namespace App\Http\Resources;

use App\Models\CampaignMapping;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherEarningDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $billing = new BillingService();
        $mapping = CampaignMapping::find($this->id);
        return [
            'mapping_id' => $this->id,
            'campaign_name' => $this->campaign->campaign_name,
            'advertiser_name' => $this->campaign->advertiser->name ?? null,
            'asset_name' => $this->publisherAsset->asset->name ?? null,
            'zone' => [
                'width' => $this->publisherZone->width,
                'height' => $this->publisherZone->height,
            ],
            'hours_billed' => $billing->calculateCampaignMappingHours($mapping),
            'price_per_hour' => $this->publisherAsset->price_per_hour,
            'amount_earned' => $billing->calculateCampaignMappingBill($mapping),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
        ];
    }
}
