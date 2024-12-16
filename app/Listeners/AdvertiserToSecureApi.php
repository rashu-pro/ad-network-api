<?php

namespace App\Listeners;

use App\Events\AdvertiserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AdvertiserToSecureApi
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
        // Call the api to store advertiser as user to secure api
        // API will be provided from secure-api
    }
}
