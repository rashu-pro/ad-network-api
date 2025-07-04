<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AssetCategory;
use App\Models\AssetType;

class AssetCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assetTypeOffline = AssetType::where('name', 'Offline')->first();

        if($assetTypeOffline){
            AssetCategory::firstOrCreate(
                [
                    'name' => 'Magazine',
                    'description' => 'Magazine',
                    'asset_type_id' => $assetTypeOffline->id,
                ]
            );

            AssetCategory::firstOrCreate(
                [
                    'name' => 'Newsletter',
                    'description' => 'Newsletter',
                    'asset_type_id' => $assetTypeOffline->id,
                ]
            );
        }

    }
}
