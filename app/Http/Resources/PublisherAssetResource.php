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
            'asset_id' => $this->asset_id,
            'asset_name' => $this->asset?->name,
            'asset_type' => $this->asset?->assetType?->name,
            'asset_category' => $this->asset?->assetCategory?->name,
            'url' => $this->url,
            'min_duration_in_hour' => $this->min_duration_in_hour,
            'price_per_hour' => $this->price_per_hour,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,

            // Feature image
            'feature_image' => $this->getFirstMediaUrl('feature'),

            // Gallery images
            'image_gallery' => $this->getMedia('gallery')->map(function ($media) {
                return $media->getUrl();
            })->values(),
        ];
    }
}
