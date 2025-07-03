<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherAssetResource extends JsonResource
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
            'asset_name' => $this->asset->name,
            'asset_type' => $this->asset->type,
            'url' => $this->url,
            'min_duration_in_hour' => $this->min_duration_in_hour,
            'price_per_hour' => $this->price_per_hour,
        ];
    }
}
