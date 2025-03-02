<?php

use App\Enums\CampaignStatus;
use App\Enums\PublisherCampaignStatus;
use App\Enums\RolesEnum;
use App\Enums\TokenAbility;
use App\Events\CampaignPublished;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Models\User;
use App\Models\Zone;
use App\Services\AdvertiserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\UserRegisteredController;
use App\Http\Controllers\Api\Auth\UserLoginController;
use App\Http\Controllers\Api\Admin\AssetController;
use App\Http\Controllers\Api\Admin\ZoneController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponse;
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('admin')->group(function () {
    Route::post('/login',[AdminLoginController::class,'login']);
    Route::post('/get-access-token',[AdminLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('admin')->user();
    })->middleware('auth:admin');

    Route::get('/assets', [AssetController::class, 'allAssets'])
        ->middleware('auth:admin');
    Route::get('/assets/active', [AssetController::class, 'allActiveAssets'])
        ->middleware('auth:admin');
    Route::post('/assets/add', [AssetController::class, 'createAsset'])
        ->middleware('auth:admin');
    Route::post('/assets/delete/{id}', [AssetController::class, 'deleteAsset'])
        ->middleware('auth:admin');

    Route::get('/zones', [ZoneController::class, 'allZones'])
        ->middleware('auth:admin');
    Route::get('/view-zone/{id}', [ZoneController::class, 'viewZone'])
        ->middleware('auth:admin');
    Route::post('/zones/add', [ZoneController::class, 'createZone'])
        ->middleware('auth:admin');
    Route::post('/zones/update/{id}', [ZoneController::class, 'updateZone'])
        ->middleware('auth:admin');
    Route::post('/zones/delete/{id}', [ZoneController::class, 'deleteZone'])
        ->middleware('auth:admin');
});
Route::prefix('user')->group(function () {
    Route::post('/register', [UserRegisteredController::class, 'userCreate'])
        ->middleware('guest:api');
    Route::post('/existing-secure-api-user-register', [UserRegisteredController::class, 'existingSecureApiUserCreate'])
        ->middleware('guest:api');
    Route::post('auth/login',[UserLoginController::class,'login'])
        ->middleware('guest:api');
    Route::post('auth/get-access-token',[UserLoginController::class,'refresh'])
        ->middleware('auth:api');
    Route::get('/profile',function (Request $request) {
        return Auth::guard('api')->user();
    })->middleware(['auth:api','abilities:'.TokenAbility::ACCESS_API->value]);
});
Route::prefix('advertiser')->middleware('auth:api')->group(function (){
    Route::post('/subscribe',[\App\Http\Controllers\Api\PaymentController::class,'advertiserSubscription'])->middleware('auth:api');
    Route::get('/available-publishers',[\App\Http\Controllers\Api\AdvertiserOptController::class,'availablePublishers']);
    Route::post('/create-campaign',[\App\Http\Controllers\Api\AdvertiserOptController::class,'createCampaign'])->middleware('permission:create campaign,api');
    Route::get('/campaign/{campaign}/zones',[\App\Http\Controllers\Api\AdvertiserOptController::class,'getUniqueZones'])->middleware('can:update,campaign');
    Route::post('/upload-campaign-banner/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'uploadCampaign'])->middleware('can:uploadBanner,campaign');
    Route::post('/campaign/{campaign}/payment',[\App\Http\Controllers\Api\PaymentController::class,'campaignPayment'])->middleware('can:update,campaign');
    Route::post('/update-campaign/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'updateCampaign'])->middleware('can:update,campaign');
    Route::get('/campaigns',[\App\Http\Controllers\Api\AdvertiserOptController::class,'allCampaigns'])->middleware('permission:view own campaign,api');
    Route::get('/campaign/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'getCampaign'])->middleware('can:getCampaign,campaign');
    Route::get('/upload-campaign-banner-for-mapping/{campaignMapping}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'reuploadToAsset'])->middleware('can:reuploadBanner,campaignMapping');
});

Route::prefix('publisher')->middleware('auth:api')->group(function (){
    Route::get('/campaign/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'getCampaign']);
    Route::post('/set-asset',[\App\Http\Controllers\Api\PublisherOtpController::class,'setAsset'])->middleware('permission:create asset,api');
    Route::post('/campaign-approval/{campaign}',[\App\Http\Controllers\Api\PublisherOtpController::class,'publishCampaign'])->middleware('can:updateStatus,campaign');
    Route::get('/assets',[\App\Http\Controllers\Api\PublisherOtpController::class,'assets'])->middleware('role:ad_publisher,api');
    Route::get('/all-assets', [AssetController::class, 'allAssets'])->middleware('role:ad_publisher,api');
    Route::get('/zones/{assetId}', [ZoneController::class, 'zonesByAssetId'])->middleware('role:ad_publisher,api');
    Route::get('/campaigns',[\App\Http\Controllers\Api\PublisherOtpController::class,'allCampaigns'])->middleware('role:ad_publisher,api');
    Route::get('/available-zones/{asset}', [\App\Http\Controllers\Api\PublisherOtpController::class,'availableZones'])->middleware('role:ad_publisher,api');
    Route::post('/get-campaign-ad-zone-id-by-mapping/{campaignMapping}', [\App\Http\Controllers\Api\PublisherOtpController::class,'getCampaignScript'])->middleware('can:getAdserverZoneIdByMapping,campaignMapping');
});
//Route::post('user/register',[UserRegisteredController::class,'userCreate']);
Route::get('company-asset/{companyKey}', function($companyKey){
    $user = User::where('secure_api_id', $companyKey)->firstOrFail()->toArray();
   return response()->json([
        'success' => true,
        'message' => 'User found',
        'data' => $user->assets->toArray() ?? [],
    ], 200);
});

Route::prefix('external')->group(function (){
    Route::get('campaign/{companyKey}', function($companyKey){
        return \App\Http\Resources\CampaignResource::collection(User::where('secure_api_id', $companyKey)->first()->campaigns);
    });
    Route::post('/campaign/{id?}', function (Request $request, int $id = null){
        $campaign = Campaign::find($id);
        if(!$campaign){
            $request->validate([
                'asset_id' => 'required|integer|exists:publisher_assets,id',
                'banner' => 'required|file|mimes:jpg,jpeg,png',
                'companyKey' => 'required',
                'campaign_name' => 'required',
                'target_url' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
            ]);
            $user = User::where('secure_api_id',$request->companyKey)->firstOrFail();

            $campaignData = $request->only([
                'campaign_name', 'target_url'
            ]);
            $campaignData['advertiser_id'] = $user->id;
            $campaignData['publisher_id'] = $user->id;
            $publisher_adserver_id = $user->publisher_advertiser_id;

            //get publisher_advertiser_id
            if(!$publisher_adserver_id){
                // Payload data
                $payload = [
                    'advertiserName' => $user->name ?? 'test_advertiser_'.$user->id,
                    'contactName'    => $user->name ?? 'test_advertiser_'.$user->id,
                    'emailAddress'   => $user->email,
                    'username'       => $user->email,
                ];

                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/adv/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
                $advertiser_id = $response->object()->advertiserId;

                //After the successful response add the advertiser_id into database
                $user->publisher_advertiser_id = $advertiser_id;
                $publisher_advertiser_id = $advertiser_id;
                $user->save();
                $user->refresh();
            }

            $campaignData['advertiser_adserver_id'] = $publisher_adserver_id;
            $campaignData['status'] = CampaignStatus::PUBLISH;
            $campaignData['payment_status'] = \App\Enums\PaymentStatus::PAID;
            //campaign create
            $campaign = Campaign::create($campaignData);
            $targetAsset = $user->assets->find($request->asset_id);

            //mapping create
            \App\Models\CampaignMapping::create([
                'campaign_id' => $campaign->id,
                'advertiser_id' => $user->id,
                'publisher_id' => $user->id,
                'publisher_asset_id' => $targetAsset->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'calculated_price' => 0,
                'is_active' => false,
                'publisher_zone_id' => $targetAsset->zone_id,
                'publisher_zone_adserver_id' => $targetAsset->zone_adserver_id,
                'status' => PublisherCampaignStatus::APPROVE,
            ]);
        }

        //updating banner
        $mapping = $campaign->mappings()->where('publisher_id',$user->id)
            ->where('publisher_zone_id',$targetAsset->zone_id)
            ->where('publisher_asset_id',$targetAsset->id)
            ->firstOrFail();
//    dd($mappings);

        if(empty($mapping)){
            return response()->json([
                'success' => false,
                'message' => 'Provided zone or publisher is not associated with this campaign',
                'data' => $data ?? [],
            ], 400);
        }

        $zone = Zone::findOrFail($targetAsset->zone_id);
        $validator = Validator::make($request->all(), [
            'banner' => 'required|file|mimes:jpg,jpeg,png|dimensions:width=' . $zone->width . ',height=' . $zone->height,
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator,'Invalid data',$validator->errors());
        }


        if($mapping->hasMedia('banner')){
            $mapping->clearMediaCollection('banner');
        }
        $mapping->addMedia($request->file('banner'))
            ->withCustomProperties([
                'publisher_id' => $mapping->publisher_id,
                'publisher_zone_id' => $mapping->publisher_zone_id,
                'publisher_asset_id' => $mapping->publisher_asset_id,
                'campaign_id' => $campaign->id,
            ])
            ->preservingOriginal()
            ->toMediaCollection('banner');



        event(new CampaignPublished($campaign));
        return response()->json([
            'success' => true,
            'message' => 'uploaded successfully',
            'data' => [
                'campaign_id' => $campaign->id,
                'url' => [
                    $mapping->hasMedia('banner') ? $mapping->getFirstMedia('banner')->getUrl() : ''
                ]
            ],
        ], 200);
    });
    Route::delete('/campaign/{companyKey}/{id}/', function ($companyKey, $id) {
        try{
            $campaign = User::where('secure_api_id', $companyKey)->firstOrFail()->campaigns()->where('id', $id)->firstOrFail();
            foreach ($campaign->mappings as $mapping){
                $mapping->clearMediaCollection('banner');
                $endpoint = env('AD_SERVER_BASE_URL').'/zon/'.$mapping->pubisher_zone_adserver_id;
                Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->delete($endpoint);
                $mapping->delete();
            }
            $publisherAssetIds = $campaign->mappings->pluck('publisher_asset_id')->unique();

            //updating client(publisher)
            foreach ($publisherAssetIds as $publisherAssetId) {
                // Retrieve all mappings for this publisher_asset_id from the database
                $mappings = CampaignMapping::where('publisher_asset_id', $publisherAssetId)->get();

                if ($mappings->isEmpty()) {
                    Log::warning("No campaign mappings found for publisher_asset_id: {$publisherAssetId}");
                    continue;
                }

                // Ensure it's an online asset before proceeding
                if ($mappings->first()->publisherAsset->asset->type != 'online') {
                    continue;
                }

                $targetUrl = $mappings->first()->publisherAsset->url ?? null;
                if (!$targetUrl) {
                    Log::warning("No target URL found for publisher_asset_id: {$publisherAssetId}");
                    continue;
                }

                // Get unique codes from all mappings for this publisher_asset_id
                $codes = $mappings->pluck('code')->unique()->values()->toArray();

                try {
                    $payload = [
                        'publisher_asset_id' => $publisherAssetId,
                        'zone_scripts' => $codes,
                    ];

                    // Send data to the publisher's webhook URL
                    $response = Http::post("{$mappings->first()->publisherAsset->webhook_path}", $payload);

                    Log::info("Webhook Response for {$publisherAssetId} {$response->status()}: {$response->body()}");

                    if (!$response->ok()) {
                        Log::error("Failed to send campaign codes for {$publisherAssetId}: {$response->body()}", [
                            'publisher_asset_id' => $publisherAssetId,
                            'payload' => $payload,
                            'target_url' => $targetUrl,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error sending campaign codes", [
                        'publisher_asset_id' => $publisherAssetId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $campaign->delete();
            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted',
            ]);
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception){
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 400);
        }
    });
});

