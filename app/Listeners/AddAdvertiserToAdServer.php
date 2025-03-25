<?php

namespace App\Listeners;

use App\Events\AdvertiserRegistered;
use App\Facades\SecureApi;
use App\Models\Advertiser;
use App\Models\User;
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
        $secureAdvertiser = SecureApi::getUser($advertiserRegistered->secure_api_id,$advertiserRegistered->email);
        // Payload data
        $payload = [
            'advertiserName' => $secureAdvertiser['name'] ?? 'test_advertiser_'.$advertiserRegistered->id,
            'contactName'    => $secureAdvertiser['name'] ?? 'test_advertiser_'.$advertiserRegistered->id,
            'emailAddress'   => $advertiserRegistered->email,
            'username'       => $advertiserRegistered->email,
        ];

        // Send GET request with Basic Auth
        $endpoint = env('AD_SERVER_BASE_URL').'/adv/new';
        $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
        $advertiser_id = $response->object()->advertiserId;

        //After the successful response add the advertiser_id into database
        $advertiser = User::find($advertiserRegistered->id);
        $advertiser->adserver_id = $advertiser_id;
        $advertiser->save();
    }
}
