<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetType;
use App\Models\AssetValuation;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class AssetDataSeeder extends Seeder
{
    public function run()
    {
        $assetType = AssetType::where('name', 'Online')->first();
        $assetTypeOffline = AssetType::where('name', 'Offline')->first();
        $assetCategory = AssetCategory::where('name', 'Magazine')->first();
        // Define assets with slugs
        $assets = [
            [
                'name' => 'Website',
                'slug' => 'website',
                'asset_type_id' => $assetType?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile',
                'slug' => 'mobile',
                'asset_type_id' => $assetType?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Display',
                'slug' => 'digital-display',
                'asset_type_id' => $assetType?->id,
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
            $asset = Asset::create($assetData);

            // Create Asset Valuations for each Asset
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

            // Create Zones for each Asset based on type
            switch ($asset->slug) {
                case 'website':
                    $zoneData = [
                        'zone_name' => 'header',
                        'width' => 1200,
                        'height' => 200,
                        'type_id' => 1,
                    ];
                    break;

                case 'mobile':
                    $zoneData = [
                        'zone_name' => 'full_screen',
                        'width' => 778,
                        'height' => 436,
                        'type_id' => 2,
                    ];
                    break;

                case 'digital-display':
                    $zoneData = [
                        'zone_name' => 'digital_banner',
                        'width' => 1920,
                        'height' => 1080,
                        'type_id' => 3,
                    ];
                    break;

                default:
                    $zoneData = [];
                    break;
            }

            if (!empty($zoneData)) {
                $asset->zones()->create($zoneData);
            }
        }
    }
}
