<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetType;
use Illuminate\Database\Seeder;

class AssetDataSeeder extends Seeder
{
    public function run()
    {
        $assetTypeOnline = AssetType::where('name', 'Online')->first();
        $assetTypeOffline = AssetType::where('name', 'Offline')->first();
        $assetCategory = AssetCategory::where('name', 'Magazine')->first();

        $assets = [
            [
                'name' => 'Website',
                'slug' => 'website',
                'asset_type_id' => $assetTypeOnline?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile',
                'slug' => 'mobile',
                'asset_type_id' => $assetTypeOnline?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Display',
                'slug' => 'digital-display',
                'asset_type_id' => $assetTypeOnline?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Offline Ad Space',
                'slug' => 'offline-adspace',
                'asset_type_id' => $assetTypeOffline?->id,
                'asset_category_id' => $assetCategory?->id,
                'is_active' => true,
            ],
        ];

        foreach ($assets as $assetData) {
            // Check if asset already exists
            $asset = Asset::where('slug', $assetData['slug'])->first();

            if ($asset) {
                // Update specific fields only
                $asset->name = $assetData['name'];
                $asset->is_active = $assetData['is_active'];

                // Only update type for online slugs
                if (in_array($asset->slug, ['website', 'mobile', 'digital-display'])) {
                    $asset->asset_type_id = $assetTypeOnline?->id;
                }

                if (isset($assetData['asset_category_id'])) {
                    $asset->asset_category_id = $assetData['asset_category_id'];
                }

                $asset->save();
            } else {
                $asset = Asset::create($assetData);
            }

            // Create Asset Valuations only if none exist
            if ($asset->valuations()->count() === 0) {
                $valuations = [
                    [
                        'has_url' => true,
                        'min_population' => 1000,
                        'max_population' => 5000,
                        'min_duration_in_hour' => 1,
                        'max_price_per_hour' => 50.00,
                    ],
                    [
                        'has_url' => false,
                        'min_population' => 500,
                        'max_population' => 1000,
                        'min_duration_in_hour' => 2,
                        'max_price_per_hour' => 30.00,
                    ],
                ];

                foreach ($valuations as $valuationData) {
                    $asset->valuations()->create($valuationData);
                }
            }

            // Add zones if none exist
            if ($asset->zones()->count() === 0) {
                $zoneData = match ($asset->slug) {
                    'website' => [
                        'zone_name' => 'header',
                        'width' => 1200,
                        'height' => 200,
                        'type_id' => 1,
                    ],
                    'mobile' => [
                        'zone_name' => 'full_screen',
                        'width' => 778,
                        'height' => 436,
                        'type_id' => 2,
                    ],
                    'digital-display' => [
                        'zone_name' => 'digital_banner',
                        'width' => 1920,
                        'height' => 1080,
                        'type_id' => 3,
                    ],
                    default => [],
                };

                if (!empty($zoneData)) {
                    $asset->zones()->create($zoneData);
                }
            }
        }
    }
}
