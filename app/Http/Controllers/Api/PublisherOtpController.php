<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublisherCampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetZoneResource;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\PublisherAssetResource;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use App\Repositories\Interfaces\AssetValuationRepositoryInterface;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use function Symfony\Component\String\s;

class PublisherOtpController extends Controller
{
    use ApiResponse;
    protected AssetRepositoryInterface $asr;
    protected AssetValuationRepositoryInterface $asrv;
    protected CampaignRepositoryInterface $cmp;

    public function __construct(AssetRepositoryInterface $asr, AssetValuationRepositoryInterface $asrv, CampaignRepositoryInterface $cmp)
    {
        $this->asr = $asr;
        $this->asrv = $asrv;
        $this->cmp = $cmp;
    }

    public function assets()
    {
        $user = Auth::guard('publisher')->user();
        return $this->successResponse('Publisher assets',PublisherAssetResource::collection($user->assets()->get()));
    }

    public function setAsset(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|integer|exists:assets,id',
            'zone_id' => 'required|integer|exists:zones,id',
            'min_population' => 'required|integer',
            'max_population' => 'nullable|integer',
            'min_duration_in_hour' => 'required|numeric',
            'price_per_hour' => 'required|numeric',
            'url' => 'nullable|string',
        ]);
        $user = Auth::guard('publisher')->user();
        $data = $request->only(['asset_id','min_duration_in_hour','price_per_hour','url', 'zone_id']);
        $validator = $this->asrv->validateAsset($request->asset_id,$request->min_population,$request->max_population ?? null);
        $asset = $this->asr->find($data['asset_id']);
        $zone = $asset->zones->where('id', $request->zone_id)->firstOrFail();

        if($asset->type == 'online' && (!$request->has('url') || $request->get('url') == null)){
            return $this->errorResponse(message: 'Url is required for online asset',status: 422);
        }
        if(!$validator){
            return $this->errorResponse(message: 'Asset validation not found',status: 404);
        }
        if($validator->max_price_per_hour < $request->price_per_hour){
            return $this->errorResponse('Price is not acceptable for the mentioned population');
        }
        if($validator->min_duration_in_hour > $request->min_duration_in_hour){
            return $this->errorResponse('Duration is not acceptable for the mentioned population');
        }

        // Payload data
        $payload = [
            'agencyId' => 1,
            'publisherName' => $data['url'],
            'website' => $data['url'],
            'contactName' => $user->name,
            'emailAddress' => $user->email,
        ];

        // Send GET request with Basic Auth
        $endpoint = env('AD_SERVER_BASE_URL').'/pub/new';
        $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
        $publisher_adserver_id = $response->object()->publisherId;
        $data['publisher_adserver_id'] = $publisher_adserver_id;

        // Payload data
        $payload = [
            'publisherId' => $publisher_adserver_id,
            'zoneName' => $zone->zone_name,
            'type' => 0,
            'width' => $zone->width,
            'height' => $zone->height,
        ];

        // Send GET request with Basic Auth
        $endpoint = env('AD_SERVER_BASE_URL').'/zon/new';
        $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
        $zone_adserver_id = $response->object()->zoneId;
        $data['zone_adserver_id'] = $zone_adserver_id;

        $publisherAsset = $user->assets()->create($data);
        return $this->successResponse(message: "Asset added to the publisher",data: (array)$publisherAsset);
    }

    public function updateCampaignStatus($id,Request $request)
    {
        $allowedStatues = [
            CampaignStatus::CONDITIONALLY_REJECT->value,
            CampaignStatus::CONDITIONALLY_APPROVE->value,
            CampaignStatus::APPROVE->value,
        ];
        $request->validate([
            'status' => 'required|string|in:'.implode(',',$allowedStatues),
            'note' => 'nullable|string'
        ]);
        $this->cmp->update($id,$request->only(['status','note']));
        return $this->successResponse(message: "Campaign status updated successfully");
    }

    public function allCampaigns()
    {
        $user = Auth::guard('publisher')->user();
        $mappings = CampaignMapping::where('publisher_id',$user->id)->pluck('campaign_id')->toArray();
        return $this->successResponse('All campaigns',CampaignResource::collection(Campaign::whereIn('id',$mappings)->where('is_draft',false)->get()));
    }

    public function availableZones($asset_id)
    {
        $zones = $this->asr->find($asset_id)->zones()->get();
        return $this->successResponse('Available zones', AssetZoneResource::collection($zones));
    }

    public function publishCampaign($campaignId, Request $request)
    {
        $request->validate([
            'zone_id' => 'required|integer|exists:campaign_mappings,publisher_zone_id',
            'status' => 'required|string|in:'.implode(',',PublisherCampaignStatus::values()),
        ]);
        $campaign = Campaign::findOrFail($campaignId);
        if(
            $campaign->status != CampaignStatus::PUBLISH || $campaign->payment_status != PaymentStatus::PAID

        ){
            return $this->errorResponse(message: 'Campaign is not published or paid',errors: [
                'campaign' => 'Campaign status is not published',
            ],status:422);
        }
        $user = Auth::guard('publisher')->user();
        $campaignMapping = CampaignMapping::where('publisher_id',$user->id)->where('campaign_id',$campaignId)
            ->where('publisher_zone_id',$request->zone_id)
            ->firstOrFail();
        $campaignMapping->update([
            'status' => $request->status,
            'note' => $request->note
        ]);
        $campaignMapping->refresh();
        if($campaignMapping->status == PublisherCampaignStatus::APPROVE){
            // Payload data
            $payload = [
                'advertiserId' => $campaign->advertiser->adserver_id,
                'campaignName' => $campaign->campaign_name,
                'startDate' => $campaign->start_date,
                'endDate' => $campaign->end_date,
                'impressions' => 10000,
                'revenueType' => 1,
                'revenue' => 12.50,
                'weight' => 1
            ];

            // Send GET request with Basic Auth
            $endpoint = env('AD_SERVER_BASE_URL').'/cam/new';
            $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);

            $campaign_adserver_id = $response->object()->campaignId;
            $campaignMapping->update([
                'campaign_adserver_id' => $campaign_adserver_id,
            ]);
            $campaignMapping->refresh();


            if(!$campaignMapping->hasMedia('banner')){
                return $this->errorResponse(message: 'Campaign or its banner does not exists',errors: [
                    'campaign' => 'Campaign or its banner does not exists',
                ],status:422);
            }
            $banner = $campaignMapping->getFirstMedia('banner');

            // Payload data
            $payload = [
                'campaignId' => $campaignMapping->campaign_adserver_id,
                'bannerName' => $banner->name,
                'storageType' => "url",
                'imageURL' => $banner->getUrl(),
                'url' => $campaign->target_url,
                'width' => $campaignMapping->publisherZone->width,
                'height' => $campaignMapping->publisherZone->height
            ];

            // Send GET request with Basic Auth
            $endpoint = env('AD_SERVER_BASE_URL').'/bnn/new';
            $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);

            $banner_adserver_id = $response->object()->bannerId;
            $campaignMapping->update([
                'banner_adserver_id' => $banner_adserver_id,
            ]);

            $campaignMapping->refresh();

            $endpoint = env('AD_SERVER_BASE_URL').'/zon/'.$campaignMapping->publisher_zone_adserver_id.'/cam/'.$campaignMapping->campaign_adserver_id;
            $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint);
            $isActive = $response->body() === '{"OK"}';
            $campaignMapping->update([
                'is_active' => $isActive,
            ]);
            $campaignMapping->refresh();
            if($campaignMapping->is_active){
                return $this->successResponse('Campaign published to '.$campaignMapping->publisherAsset->asset->name);
            }
            return $this->errorResponse(message: 'Campaign failed to published at '.$campaignMapping->publisherAsset->asset->name);
        }
      return $this->successResponse(message: 'Campaign status is updated');
    }
}
