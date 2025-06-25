<?php

namespace Database\Seeders;

use App\Models\Publisher;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesPermissionsSeeder::class);
        $this->call(AssetTypeSeeder::class);
        $this->call(AssetCategorySeeder::class);
        $this->call(AssetDataSeeder::class);
    }
}
