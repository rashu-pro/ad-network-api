<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\Publisher;
use App\Services\AdvertiserService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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
        try{
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
        }catch (\Exception $e){
            return $this->errorResponse($e->getMessage(),$e->getTrace());
        }
    }

    public function uploadCampaign($id, Request $request)
    {
        try{
            $request->validate([
                'banner' => 'required|file|mimes:jpg,jpeg,png'
            ]);
            $campaign = Campaign::with()->findOrFail($id);
            $zones = $campaign->
            $campaign->addMedia($request->banner)->toMediaCollection('banner');
        }catch (\Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

    public function updateCampaign($id, Request $request)
    {

        $this->advertiserService->updateCampaign($id, $request->only(
            'campaign_name', 'target_url', 'is_draft', 'status'
        ));
        return $this->successResponse(message: 'updated successfully', data: new CampaignResource(Campaign::findOrFail($id)));
    }

}
