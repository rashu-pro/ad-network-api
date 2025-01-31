<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Enums\PublisherCampaignStatus;
use App\Events\CampaignPublished;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponse;
    public function advertiserSubscription(Request $request)
    {
        $user = Auth::guard('advertiser')->user();
        $user->update([
            'subscription_id' => Str::uuid(),
            'subscription_expires_at' => now()->addDays(365),
        ]);
        $advertiser = Advertiser::findOrFail($user->id);
//        $remoteAdvertiser  = SecureApi::getAdvertiser($advertiser->secure_api_id);
        // Payload data
        $payload = [
            'advertiserName' => 'Advertiser ' . $advertiser->id,
            'contactName'    =>  'Advertiser ' . $advertiser->id,
            'emailAddress'   => $advertiser->email,
            'username'       => $advertiser->email,
        ];

        // Send GET request with Basic Auth
        $endpoint = env('AD_SERVER_BASE_URL').'/adv/new';
        $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
        $advertiser_id = $response->object()->advertiserId;


        $advertiser->adserver_id = $advertiser_id;
        $advertiser->save();


        return $this->successResponse('Advertiser subscription successful');
    }

    public function campaignPayment($id)
    {
        $user = Auth::guard('advertiser')->user();
        $campaign = $user->campaigns()->where('id', $id)->first();
        $campaign->update([
            'payment_status' => PaymentStatus::PAID,
        ]);
        $campaign->refresh();
        $mappings = $campaign->campaignMappings()->get();
        if($campaign->payment_status == PaymentStatus::PAID){
            foreach($mappings as $mapping){
                $mapping->update([
                    'status' => PublisherCampaignStatus::APPROVE,
                ]);
                $mapping->refresh();
            }
        }
        dispatch(new CampaignPublished($campaign));
        return $this->successResponse('Payment successful');
    }
}
