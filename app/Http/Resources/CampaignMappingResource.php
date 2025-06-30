<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignMappingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $asset = $this->publisherAsset;

        $userTimezone = $request->user()?->timezone ?? config('app.timezone');
        $start_date = $this->start_date ? Carbon::parse($this->start_date)->timezone($userTimezone)->translatedFormat('jS F, Y') : null;
        $end_date = $this->end_date ? Carbon::parse($this->end_date)->timezone($userTimezone)->translatedFormat('jS F, Y') : null;
        return [
            'id' => $asset->id,
            'mapping_id' => $this->id,
            'name' => $asset->asset->name,
            'asset_type' => $asset->asset->assetType->name,
            'price_per_hour' => $asset->price_per_hour,
            'calculated_price' => $this->calculated_price,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'zone_id' => $this->publisher_zone_id,
            'zone_width' => $this->publisher_zone_id ? $this->publisherZone?->width : null,
            'zone_height' => $this->publisher_zone_id ? $this->publisherZone?->height : null,
            'zone_adserver_id' => $this->publisher_zone_adserver_id,
            'campaign_adserver_id' => $this->campaign_adserver_id,
            'publisher_asset_url' => $asset->url,
            'target_url' => $this->campaign->target_url,
            'is_active' => $this->is_active,
            'note' => $this->notes,
            'banner' => $this->hasMedia('banner') ? $this->getFirstMedia('banner')->getUrl() : '#',
            'status' => $this->status,
        ];
    }
}
