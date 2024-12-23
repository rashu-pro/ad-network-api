<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
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
            'min_population' => 'required|integer',
            'max_population' => 'nullable|integer',
            'min_duration_in_hour' => 'required|numeric',
            'price_per_hour' => 'required|numeric',
            'url' => 'nullable|string',
        ]);
        $user = Auth::guard('publisher')->user();
        $data = $request->only(['asset_id','min_duration_in_hour','price_per_hour','url']);
        $validator = $this->asrv->validateAsset($request->asset_id,$request->min_population,$request->max_population ?? null);
        $asset = $this->asr->find($data['asset_id']);
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
}
