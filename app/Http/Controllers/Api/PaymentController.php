<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublisherCampaignStatus;
use App\Events\CampaignPublished;
use App\Events\SendCampaignCodesToPublishers;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignPaymentResource;
use App\Models\Campaign;
use App\Models\CampaignPayment;
use App\Models\User;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponse;
    public function advertiserSubscription(Request $request)
    {
        $user = Auth::guard('api')->user();
        $user->update([
            'subscription_id' => Str::uuid(),
            'subscription_expires_at' => now()->addDays(365),
        ]);
        $advertiser = User::findOrFail($user->id);
//        $remoteAdvertiser  = SecureApi::getUser($advertiser->secure_api_id);
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

    public function campaignPayment(Campaign $campaign)
    {
        $user = Auth::guard('api')->user();
        DB::beginTransaction();
        $campaign->update([
            'payment_status' => PaymentStatus::PAID->value,
            'is_draft' => false,
            'status' => CampaignStatus::PUBLISH->value
        ]);
        $campaign->refresh();
        $mappings = $campaign->campaignMappings()->get();
        $publishers = [];
        if($campaign->payment_status == PaymentStatus::PAID){
            foreach($mappings as $mapping){
                $mapping->update([
                    'status' => PublisherCampaignStatus::APPROVE->value,
                ]);
                $mapping->refresh();
                if($mapping->publisher){
                    $publisher = SecureApi::getUser($mapping->publisher->secure_api_id, $mapping->publisher->email);
                    if($publisher){
                        $publishers[] = $publisher['businessName'];
                        $advertiser = SecureApi::getUser($mapping->advertiser->secure_api_id, $mapping->advertiser->email);
                        SecureApi::sendSingleEmail(
                            templateIdentifier: "AD_NETWORK_ADVERTISEMENT_RECEIVED",
                            recipient: $mapping->publisher->email,
                            placeholders: [
                                "ContactPersonName" => $publisher['contactInfo']['name'],
                                "CampaignTitle" => $mapping->campaign->campaign_name,
                                "StartDate" => date_format(date_create($mapping->start_date),"d M, Y"),
                                "EndDate" => date_format(date_create($mapping->end_date),"d M, Y"),
                                "Advertiser" => $advertiser['businessName'].'('.$advertiser['contactInfo']['email'].')' ?? '',
                                "Description" => "<a href='" . env('FRONTEND_URL') . "/login'>View Advertisement". "</a>",
                            ],
                            cc: "rashu@techknowworld.com"
                        );
                    }
                }
            }
        }

        DB::commit();
//        event(new CampaignPublished($campaign));
        $secureApiUser = SecureApi::getUser($user->secure_api_id, $user->email);
        SecureApi::sendSingleEmail(
            templateIdentifier: "AD_NETWORK_ADVERTISEMENT_APPROVAL",
            recipient: $user->email,
            placeholders: [
                "ContactPersonName" => $secureApiUser['contactInfo']['name'],
                "CampaignTitle" => $campaign->campaign_name,
                "StartDate" => $campaign->mappings ? date_format(date_create($campaign->mappings->first()->start_date),'d M, Y') : null,
                "EndDate" => $campaign->mappings ? date_format(date_create($campaign->mappings->first()->end_date),'d M, Y') : null,
                "Adpublisher" => implode(',',$publishers),
                "Description" => "<a href='" . env('FRONTEND_URL') . "/login'>". "Login in to the portal</a>",
            ]
        );
        return $this->successResponse('Payment successful');
    }

    public function makePayment(Request $request, $campaignId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:card,bank_transfer,manual',
            'reference' => 'nullable|string',
        ]);

        $campaign = Campaign::with('payments')->findOrFail($campaignId);

        $paymentService = new PaymentService();
        $paymentService->processPayment($campaign, $request->amount, $request->payment_method, $request->reference);

        return response()->json(['message' => 'Payment processed.']);
    }
}
