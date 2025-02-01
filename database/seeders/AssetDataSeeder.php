<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetValuation;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class AssetDataSeeder extends Seeder
{
    public function run()
    {
        // Define assets with slugs
        $assets = [
            [
                'name' => 'Website',
                'slug' => 'website',
                'type' => 'online',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile',
                'slug' => 'mobile',
                'type' => 'online',
                'is_active' => true,
            ],
            [
                'name' => 'Digital Display',
                'slug' => 'digital-display',
                'type' => 'offline',
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
                        'width' => 1920,
                        'height' => 600,
                        'type_id' => 1,
                    ];
                    break;

                case 'mobile':
                    $zoneData = [
                        'zone_name' => 'full_screen',
                        'width' => 1080,
                        'height' => 1920,
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
