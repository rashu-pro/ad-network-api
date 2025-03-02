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
use Symfony\Component\HttpKernel\Exception\HttpException;

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

Route::post('/update-campaign/{id?}', function (Request $request, int $id = null){
    $campaign = Campaign::find($id);
    $advertiserService = app(AdvertiserService::class);
    if(!$campaign){
        $request->validate([
            'zoneId' => 'required|integer|exists:publisher_assets,zone_id',
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

        //mapping create
        foreach ($user->assets as $asset){
            \App\Models\CampaignMapping::create([
                'campaign_id' => $campaign->id,
                'advertiser_id' => $user->id,
                'publisher_id' => $user->id,
                'publisher_asset_id' => $asset->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'calculated_price' => 0,
                'is_active' => true,
                'publisher_zone_id' => $asset->zone_id,
                'publisher_zone_adserver_id' => $asset->zone_adserver_id,
                'status' => PublisherCampaignStatus::APPROVE,
            ]);
        }
    }

    //updating banner
    $mappings = $campaign->mappings()->where('publisher_id',$user->id)
        ->where('publisher_zone_id',$request->zoneId)
        ->get();
//    dd($mappings);

    if($mappings->count() <= 0){
        return response()->json([
            'success' => false,
            'message' => 'Provided zone or publisher is not associated with this campaign',
            'data' => $data ?? [],
        ], 400);
    }

    $zone = Zone::findOrFail($request->zoneId);
    $validator = Validator::make($request->all(), [
        'banner' => 'required|file|mimes:jpg,jpeg,png|dimensions:width=' . $zone->width . ',height=' . $zone->height,
    ]);

    if ($validator->fails()) {
        throw new ValidationException($validator,'Invalid data',$validator->errors());
    }

    $tempPath = $request->file('banner')->store('temp');
    $bannerPath = storage_path('app/private/' . $tempPath);

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
    }
    Storage::delete('app/private/'.$tempPath);

    $campaignMappings = CampaignMapping::where('campaign_id',$campaign->id)
        ->get();
    foreach($campaignMappings as $campaignMapping){
        if($campaignMapping->is_active){
            continue;
        }
        $zone = Zone::findOrFail($campaignMapping->publisher_zone_id);
        if($campaignMapping->status == PublisherCampaignStatus::APPROVE){
            // Payload data
            $payload = [
                'publisherId' => (int)$campaignMapping->publisherAsset->publisher_adserver_id,
                'zoneName' => $zone->zone_name.'_'.now(),
                'type' => 0,
                'width' => $zone->width,
                'height' => $zone->height,
            ];
            // Send GET request with Basic Auth
            $endpoint = env('AD_SERVER_BASE_URL').'/zon/new';
            $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
            $zone_adserver_id = $response->object()->zoneId;

            $campaignMapping->update([
                'publisher_zone_adserver_id' => $zone_adserver_id,
            ]);
            $campaignMapping->refresh();
            // Payload data
            $payload = [
                'advertiserId' => (int)$campaign->advertiser->adserver_id,
                'campaignName' => $campaign->campaign_name.'_'.now(),
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
                Log::error('Campaign does not have banner for zone '.$zone->name);
                return ;
            }
            $banner = $campaignMapping->getFirstMedia('banner');

            // Payload data
            $payload = [
                'campaignId' => (int)$campaignMapping->campaign_adserver_id,
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
            if(!$campaignMapping->is_active){
                throw new HttpException("Campaign was unable to publish. Try again later.");
            }
        }
    }
    return response()->json([
        'success' => true,
        'message' => 'uploaded successfully',
        'data' => [
            'campaign_id' => $campaign->id,
            'url' => $mappings->filter(function ($mapping){
                return $mapping->hasMedia('banner');
            })->map(function ($mapping) {
                return  $mapping->getFirstMedia('banner')->getUrl();
            })->toArray()
        ],
    ], 200);
});
