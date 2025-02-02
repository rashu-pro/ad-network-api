<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publisherRole = app(Role::class)->findOrCreate(RolesEnum::PUBLISHER->value,'api');
        $advertiserRole = app(Role::class)->findOrCreate(RolesEnum::ADVERTISER->value,'api');
        $adminRole = app(Role::class)->findOrCreate(RolesEnum::ADMIN->value,'api');

        $createCampaign = Permission::firstOrCreate(['name' => 'create campaign', 'guard_name' => 'api']);
        $updateCampaign = Permission::firstOrCreate(['name' => 'update campaign', 'guard_name' => 'api']);
        $uploadBanner = Permission::firstOrCreate(['name' => 'upload banner', 'guard_name' => 'api']);
        $viewCampaign = Permission::firstOrCreate(['name' => 'view own campaign', 'guard_name' => 'api']);
        $payCampaign = Permission::firstOrCreate(['name' => 'pay for campaign', 'guard_name' => 'api']);
        $updateStatus = Permission::firstOrCreate(['name' => 'update campaign status', 'guard_name' => 'api']);
        $writeNote = Permission::firstOrCreate(['name' => 'write campaign note', 'guard_name' => 'api']);
        $viewPublishedCampaigns = Permission::firstOrCreate(['name' => 'view published campaigns', 'guard_name' => 'api']);
        $createAsset = Permission::firstOrCreate(['name' => 'create asset', 'guard_name' => 'api']);
        $setAsset = Permission::firstOrCreate(['name' => 'create asset', 'guard_name' => 'api']);
        $updateAsset = Permission::firstOrCreate(['name' => 'update asset', 'guard_name' => 'api']);

        // Assign permissions to Advertiser
        $advertiserRole->givePermissionTo([
            'create campaign',
            'update campaign',
            'upload banner',
            'view own campaign',
            'pay for campaign',
        ]);

        // Assign permissions to Publisher
        $publisherRole->givePermissionTo([
            'create asset',
            'create asset',
            'update asset',
            'update campaign status',
            'write campaign note',
            'view published campaigns'
        ]);

        // Assign all permissions to Admin
        $adminRole->givePermissionTo(Permission::all());
    }
}
