<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AssetType::create(['name' => 'Online', 'description' => 'Online', 'status' => 1]);
        AssetType::create(['name' => 'Offline', 'description' => 'Offline' , 'status' => 1]);
    }
}
