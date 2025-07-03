<?php

namespace App\Listeners;

use App\Events\AdvertiserRegistered;
use App\Facades\AdServer;
use App\Facades\SecureApi;
use App\Models\Advertiser;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        try {
            $secureAdvertiser = SecureApi::getUser(
                $advertiserRegistered->secure_api_id,
                $advertiserRegistered->email
            );

            $advertiserName = $secureAdvertiser['businessName'] ?? 'test_advertiser_' . $advertiserRegistered->id;
            $contactName = $secureAdvertiser['businessName'] ?? 'test_advertiser_' . $advertiserRegistered->id;

            try {
                $advertiserId = AdServer::createAdvertiser(
                    $advertiserName,
                    $contactName,
                    $advertiserRegistered->email
                );

                $advertiser = User::find($advertiserRegistered->id);
                $advertiser->adserver_id = $advertiserId;
                $advertiser->save();
            } catch (\Throwable $e) {
                Log::error('AdServer advertiser creation failed', [
                    'email' => $advertiserRegistered->email,
                    'message' => $e->getMessage(),
                    'payload' => [
                        'advertiserName' => $advertiserName,
                        'contactName' => $contactName,
                        'emailAddress' => $advertiserRegistered->email,
                    ]
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Failed to retrieve secure advertiser info', [
                'secure_api_id' => $advertiserRegistered->secure_api_id,
                'email' => $advertiserRegistered->email,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
