<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'campaign' => [
                'id' => $this->id,
                'name' => $this->campaign_name,
                'status' => $this->status,
                'is_draft' => $this->is_draft,
            ],
            'publishers' => $this->campaignMappings()->get()->groupBy('publisher_id')->map(function ($groupedMappings) {
                // Retrieve the first mapping in the group to extract publisher details
                $firstMapping = $groupedMappings->first();
                $publisher = $firstMapping->publisher;

                return [
                    'publisher_id' => $publisher->id,
                    'publisher_name' => $publisher->name,
                    'assets' => $groupedMappings->map(function ($mapping) {
                        $asset = $mapping->publisherAsset; // Get asset details from mapping
                        return [
                            'id' => $asset->id,
                            'name' => $asset->asset->name,
                            'price_per_hour' => $asset->price_per_hour,
                            'calculated_price' => $mapping->calculated_price,
                            'start_date' => $mapping->start_date,
                            'end_date' => $mapping->end_date,
                            'zone_id' => $mapping->publisher_zone_id,
                            'zone_adserver_id' => $mapping->publisher_zone_adserver_id,
                            'banner' => $mapping->hasMedia('banner') ? $mapping->getFirstMedia('banner')->getUrl() : '#'
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(),
        ];
    }
}
