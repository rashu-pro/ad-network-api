<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertiserPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'payment_id' => $this->id,
            'amount' => round($this->amount,2),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'campaign' => [
                'id' => $this->campaign_id,
                'name' => $this->campaign->campaign_name,
            ],
            'payment_date' => $this->payment_date->toDateTimeString(),
        ];
    }
}
