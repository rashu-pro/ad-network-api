<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Models\Publisher;
use App\Models\PublisherAsset;
use App\Models\Zone;
use App\Services\AdvertiserService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdvertiserOptController extends Controller
{
    use ApiResponse;
    protected $advertiserService;
    public function __construct(AdvertiserService $advertiserService)
    {
        $this->advertiserService = $advertiserService;
    }
    public function allCampaigns()
    {
        try{
            $user = Auth::guard('advertiser')->user();
            $campaigns = CampaignResource::collection($this->advertiserService->allCampaigns($user->id));
            return $this->successResponse(message: 'All campaigns',data: $campaigns);
        }catch (\Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

    public function createCampaign(Request $request)
    {
        $request->validate([
            'publisher_ids' => 'required|array',
        ]);
        $user = Auth::guard('advertiser')->user();

        $campaignData = $request->only([
            'campaign_name', 'target_url', 'is_draft', 'start_date', 'end_date',
        ]);
        $campaignData['advertiser_id'] = $user->id;
        $campaignData['advertiser_adserver_id'] = $user->adserver_id;
        $campaign = $this->advertiserService->createCampaign($campaignData);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $days = $startDate->diffInDays($endDate) + 1;
        $minDuration = DB::table('publisher_assets')->whereIn('publisher_id',$request->publisher_ids)->min('min_duration_in_hour');
        if(($days * 24) < $minDuration){
            return $this->errorResponse('You have to run ad for at least '.$minDuration.' Hours');
        }
        $publishersData = Publisher::whereIn('id', $request->publisher_ids)
            ->has('assets')
            ->with('assets') // Eager load assets relationship
            ->get()
            ->map(function ($publisher) use ($request,$user){
                return $publisher->assets->map(function ($asset) use ($publisher,$request,$user) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    $days = $startDate->diffInDays($endDate) + 1; // Include the start day
                    // Calculate the total price
                    $calculatedPrice = $asset->price_per_hour * 24 * $days;

                    return [
                        'advertiser_id' => $user->id,
                        'publisher_id' => $publisher->id,
                        'publisher_asset_id' => $asset->id,
                        'publisher_zone_id' => $asset->zone_id,
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                        'calculated_price' => $calculatedPrice,
                        'is_active' => false,
                    ];
                })->toArray(); // Convert collection to array
            })
            ->flatten(1) // Flatten nested arrays
            ->toArray(); // Convert to a plain array
        $this->advertiserService->selectPublishers($campaign->id,$publishersData);

        return $this->successResponse(message: 'created successfully', data: new CampaignResource($campaign));
    }

    public function uploadCampaign($id, Request $request)
    {
        $request->validate([
            'banner' => 'required|file|mimes:jpg,jpeg,png',
            'publisher_id' => 'required|integer|exists:publishers,id',
            'zone_id' => 'required|integer|exists:publisher_assets,zone_id',
        ]);
        $campaign = Campaign::findOrFail($id);
        $mappings = $campaign->mappings()->where('publisher_id',$request->publisher_id)
            ->where('publisher_zone_id',$request->zone_id)
            ->get();
        $zone = $mappings->first()->publisherZone;

        $validator = Validator::make($request->all(), [
            'banner' => 'required|file|mimes:jpg,jpeg,png|dimensions:width=' . $zone->width . ',height=' . $zone->height,
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator,'Invalid data',$validator->errors());
        }
        $tempPath = $request->file('banner')->store('temp');
        $bannerPath = storage_path('app/private/' . $tempPath);
//        dd($bannerPath);
        foreach ($mappings as $mapping) {
            if($mapping->hasMedia('banner')){
                $mapping->clearMediaCollection('banner');
            }
            $mapping->addMedia($bannerPath)
                ->withCustomProperties([
                    'publisher_id' => $mapping->publisher_id,
                    'publisher_zone_id' => $mapping->publisher_zone_id,
                    'publisher_asset_id' => $mapping->publisher_asset_id,
                    'campaign_id' => $campaign->id,
                ])
                ->preservingOriginal()
                ->toMediaCollection('banner');
//            var_dump($mapping->getMedia('banner')->count());
        }
        Storage::delete('app/private/'.$tempPath);
        return $this->successResponse(message: 'uploaded successfully', data: [
            'url' => $mappings->map(function ($mapping) {
                return  $mapping->getFirstMedia('banner')->getUrl();
            })->toArray()
        ]);
    }

    public function updateCampaign($id, Request $request)
    {

        $this->advertiserService->updateCampaign($id, $request->only(
            'campaign_name', 'target_url', 'is_draft', 'status'
        ));
        return $this->successResponse(message: 'updated successfully', data: new CampaignResource(Campaign::findOrFail($id)));
    }

}
