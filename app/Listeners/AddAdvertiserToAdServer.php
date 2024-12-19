<?php

namespace App\Listeners;

use App\Events\AdvertiserRegistered;
use App\Models\Advertiser;
use Illuminate\Support\Facades\Http;

class AddAdvertiserToAdServer
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

        // Payload data
        $payload = [
            'advertiserName' => $advertiserRegistered->advertiser_name,
            'contactName'    => $advertiserRegistered->advertiser_name,
            'emailAddress'   => $advertiserRegistered->email,
            'username'       => $advertiserRegistered->email,
        ];

        // Send GET request with Basic Auth
        $endpoint = env('AD_SERVER_BASE_URL').'/adv/new';
        $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
        $advertiser_id = $response->object()->advertiserId;

        //After the successful response add the advertiser_id into database
        $advertiser = Advertiser::find($advertiserRegistered->id);
        $advertiser->advertiser_id = $advertiser_id;
        $advertiser->save();
    }
}
