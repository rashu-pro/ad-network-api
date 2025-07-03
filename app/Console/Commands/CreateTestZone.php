<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zone;

class CreateTestZone extends Command
{
    protected $signature = 'zone:create-test';
    protected $description = 'Create a dummy test zone every time this command runs';

    public function handle(): void
    {
        $zone = Zone::create([
            'asset_id' => 1, // Replace with a valid asset_id if needed
            'zone_name' => 'TestZone_' . now()->format('His'),
            'width' => 300,
            'height' => 250,
            'type_id' => 1
        ]);

        $this->info("Test Zone Created: ID {$zone->id}, Name: {$zone->zone_name}");
    }
}
