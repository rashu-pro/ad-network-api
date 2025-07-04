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
        $assetType = AssetType::where('name', 'Offline')->first();

        AssetCategory::create([
            'name' => 'Magazine',
            'description' => 'Magazine',
            'asset_type_id' => $assetType?->id
        ]);

        AssetCategory::create([
            'name' => 'Newsletter',
            'description' => 'Newsletter',
            'asset_type_id' => $assetType?->id
        ]);
    }
}
