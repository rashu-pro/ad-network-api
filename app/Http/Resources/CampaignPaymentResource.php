<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => round($this->amount,2),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'payment_date' => $this->payment_date,
            'campaign_id' => $this->campaign_id,
            'advertiser_id' => $this->advertiser_id,
            'campaign_name' => $this->campaign->campaign_name,
            'advertiser_name' => $this->advertiser->name ?? null,
        ];
    }
}
