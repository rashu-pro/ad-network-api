<?php

use App\Enums\CampaignStatus;
use App\Enums\PublisherCampaignStatus;
use App\Enums\RolesEnum;
use App\Enums\TokenAbility;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
Route::post('/update-campaign/{id?}', function (Request $request, int $id = null){
    $campaign = Campaign::find($id);
    if(!$campaign){
        $request->validate([
            'zoneId' => 'required|integer|exists:publisher_assets,zone_id',
            'banner' => 'required|file|mimes:jpg,jpeg,png',
            'companyKey' => 'required'
        ]);
        $user = User::where('secure_api_id',$request->companyKey)->firstOrFail();

        $campaignData = $request->only([
            'campaign_name', 'target_url', 'start_date', 'end_date',
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
            $user->publisher_adserver_id = $advertiser_id;
            $publisher_adserver_id = $advertiser_id;
            $user->save();
            $user->refresh();
        }

        $campaignData['advertiser_adserver_id'] = $publisher_adserver_id;
        $campaignData['status'] = CampaignStatus::PUBLISH;
        //campaign create
        $campaign = $this->advertiserService->createCampaign($campaignData);

        //mapping create
        $publishersData = User::whereHas('roles', function ($query) {
            $query->where('name', RolesEnum::PUBLISHER->value);
        })->where('id', $request->publisher_id)
            ->has('assets')
            ->with('assets')
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
                        'status' => PublisherCampaignStatus::APPROVE,
                        'calculated_price' => 0,
                        'is_active' => true,
                    ];
                })->toArray(); // Convert collection to array
            })
            ->flatten(1) // Flatten nested arrays
            ->toArray(); // Convert to a plain array
        $this->advertiserService->selectPublishers($campaign->id,$publishersData);
    }

    //updating banner
    $mappings = $campaign->mappings()->where('publisher_id',$request->publisher_id)
        ->where('publisher_zone_id',$request->zone_id)
        ->get();

    if($mappings->count() <= 0){
        return $this->errorResponse('Provided zone or publisher is not associated with this campaign');
    }

    $zone = Zone::findOrFail($request->zone_id);
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
    return $this->successResponse(message: 'uploaded successfully', data: [
        'campaign_id' => $campaign->id,
        'url' => $mappings->filter(function ($mapping){
            return $mapping->hasMedia('banner');
        })->map(function ($mapping) {
            return  $mapping->getFirstMedia('banner')->getUrl();
        })->toArray()
    ]);
});
