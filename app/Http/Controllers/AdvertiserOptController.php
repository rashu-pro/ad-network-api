<?php

namespace App\Http\Controllers;

use App\Services\AdvertiserService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
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
            return $this->successResponse($this->advertiserService->allCampaigns($user->id));
        }catch (\Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

    public function createCampaign(Request $request)
    {
        try{
            $data = $request->only([
               'campaign_name', 'target_url', 'is_draft'
            ]);
            $campaign = $this->advertiserService->createCampaign($data);
            return $this->successResponse(message: 'created successfully',data: (array)$campaign);
        }catch (\Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }
}
