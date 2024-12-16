<?php

namespace App\Listeners;

use App\Events\AdvertiserRegistered;
use App\Models\Advertiser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AddAdvertiserToAdserver
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AdvertiserRegistered $event): void
    {
        $advertiserRegistered = $event->advertiser;
        // Call adserver advertiser add api to add the advertiser to adserver
        // dummy advertiser id
        $advertiser_id = 999;


        //After the successful response add the advertiser_id into database
        $advertiser = Advertiser::find($advertiserRegistered->id);
        $advertiser->advertiser_id = $advertiser_id;
        $advertiser->save();
    }
}
