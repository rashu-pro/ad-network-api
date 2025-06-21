<?php

use App\Enums\CampaignStatus;
use App\Enums\PublisherCampaignStatus;
use App\Enums\RolesEnum;
use App\Enums\TokenAbility;
use App\Events\CampaignPublished;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdRatingController;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use App\Models\Asset;
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
    Route::post('/upload-campaign-banner/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'uploadCampaign']);
    Route::post('/publish-campaign-asset/{campaignMapping}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'publishInAsset']);
    Route::post('/campaign/{campaign}/payment',[\App\Http\Controllers\Api\PaymentController::class,'campaignPayment'])->middleware('can:update,campaign');
    Route::post('/update-campaign/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'updateCampaign'])->middleware('can:update,campaign');
    Route::get('/campaigns',[\App\Http\Controllers\Api\AdvertiserOptController::class,'allCampaigns'])->middleware('permission:view own campaign,api');
    Route::get('/campaign-mappings',[\App\Http\Controllers\Api\AdvertiserOptController::class,'getMappings'])->middleware('permission:view own campaign,api');
    Route::get('/campaign/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'getCampaign'])->middleware('can:getCampaign,campaign');
    Route::post('/campaign/del/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'deleteCampaign'])->middleware('can:getCampaign,campaign');
    Route::get('/upload-campaign-banner-for-mapping/{campaignMapping}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'reuploadToAsset'])->middleware('can:reuploadBanner,campaignMapping');
    Route::get('/payments', [\App\Http\Controllers\Api\AdvertiserOptController::class,'listAdvertiserPayments'])->middleware(['auth:sanctum', 'role:advertiser']);
    Route::middleware(['role:advertiser'])->get('/payments/{payment}', [\App\Http\Controllers\Api\AdvertiserOptController::class, 'showAdvertiserPayment']);
    Route::middleware(['role:advertiser'])->prefix('campaign')->group(function () {
        Route::get('/ratings/{mapping}', [AdRatingController::class, 'show']);
        Route::post('/ratings/{mapping}', [AdRatingController::class, 'store']);
        Route::put('/ratings/{mapping}', [AdRatingController::class, 'update']);
        Route::delete('/ratings/{mapping}', [AdRatingController::class, 'destroy']);
    });

});

Route::prefix('publisher')->middleware('auth:api')->group(function (){
    Route::get('/campaign/{campaign}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'getCampaign']);
    Route::post('/set-asset',[\App\Http\Controllers\Api\PublisherOtpController::class,'setAsset'])->middleware('permission:create asset,api');
    Route::put('/update-asset/{id}', [\App\Http\Controllers\Api\PublisherOtpController::class, 'updateAsset'])->middleware('permission:update asset,api');
    Route::post('/campaign-approval/{campaign}',[\App\Http\Controllers\Api\PublisherOtpController::class,'publishCampaign'])->middleware('can:updateStatus,campaign');
    Route::post('/update-campaign-mapping-status/{campaignMapping}',[\App\Http\Controllers\Api\PublisherOtpController::class,'updateCampaignMappingStatus']);
    Route::get('/assets',[\App\Http\Controllers\Api\PublisherOtpController::class,'assets'])->middleware('role:ad_publisher,api');
    Route::get('/asset/{id}', [\App\Http\Controllers\Api\PublisherOtpController::class, 'showAsset'])->middleware('role:ad_publisher,api');
    Route::delete('/asset/{id}', [\App\Http\Controllers\Api\PublisherOtpController::class, 'deleteAsset'])->middleware('permission:delete asset,api');
    Route::get('/all-assets', [AssetController::class, 'allAssets'])->middleware('role:ad_publisher,api');
    Route::get('/zones/{assetId}', [ZoneController::class, 'zonesByAssetId'])->middleware('role:ad_publisher,api');
    Route::get('/campaigns',[\App\Http\Controllers\Api\PublisherOtpController::class,'allCampaigns'])->middleware('role:ad_publisher,api');
    Route::get('/available-zones/{asset}', [\App\Http\Controllers\Api\PublisherOtpController::class,'availableZones'])->middleware('role:ad_publisher,api');
    Route::post('/get-campaign-ad-zone-id-by-mapping/{campaignMapping}', [\App\Http\Controllers\Api\PublisherOtpController::class,'getCampaignScript'])->middleware('can:getAdserverZoneIdByMapping,campaignMapping');
    Route::get('/payments', [\App\Http\Controllers\Api\PublisherOtpController::class,'bills'])->middleware(['auth:sanctum', 'role:ad_publisher,api']);
    Route::middleware(['role:ad_publisher'])->get('earnings/{mapping}', [\App\Http\Controllers\Api\PublisherOtpController::class, 'showPublisherEarning']);
    Route::middleware(['role:ad_publisher'])->post('reject-asset-campaign/{asset}', [\App\Http\Controllers\Api\PublisherOtpController::class, 'rejectAllCampaigns']);
});
//Route::post('user/register',[UserRegisteredController::class,'userCreate']);
Route::middleware(['auth:api'])->post('/account', [AccountController::class, 'deleteAccount']);


Route::prefix('external')->group(function (){
    Route::get('company-asset/{companyKey}', function($companyKey){
        $user = User::where('secure_api_id', $companyKey)->firstOrFail();
        return response()->json([
            'success' => true,
            'message' => 'Assets',
            'data' => $user->assets->map(function($asset){
                    return [
                        'id' => $asset->id,
                        'name' => $asset->asset->name,
                        'slug' => $asset->asset->slug,
                        'zone_width' => $asset->zone->width,
                        'zone_height' => $asset->zone->height,
                        'zone_slug' => $asset->zone->zone_name,
                    ];
                }) ?? [],
        ], 200);
    });
    Route::get('campaign/{companyKey}', function($companyKey){
        $user = User::where('secure_api_id', $companyKey)->firstOrFail();
        $campaigns = CampaignMapping::where('advertiser_id', $user->id)
                                    ->where('publisher_id',$user->id)
                                    ->get()
                                    ->map(function($campaign){
                                        return [
                                            'id' => $campaign->campaign->id,
                                            'name' => $campaign->campaign->campaign_name,
                                            'start_date' => $campaign->start_date,
                                            'end_date' => $campaign->end_date,
                                            'target_url' => $campaign->campaign->target_url,
                                            'banner' => $campaign->hasMedia('banner') ? $campaign->getFirstMediaUrl('banner') : '#',
                                        ];
                                    });
        return response()->json([
            'success' => true,
            'message' => 'Campaigns',
            'data' => $campaigns,
        ]);
    });
    Route::post('/campaign/{slug}', [\App\Http\Controllers\Api\External\PublisherCampaignController::class,'storeCampaign']);
    Route::delete('/campaign/{companyKey}/{id}/', function ($companyKey, $id) {
        try{
            $campaign = User::where('secure_api_id', $companyKey)->firstOrFail()->campaigns()->where('id', $id)->firstOrFail();
//            dd($campaign->mappings);
            foreach ($campaign->mappings as $mapping){
//                dd($mapping);
                $mapping->clearMediaCollection('banner');
                $endpoint = env('AD_SERVER_BASE_URL').'/zon/'.$mapping->publisher_zone_adserver_id;
//                dd($endpoint);
                $res = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->delete($endpoint);
                if(!$res->ok()){
                    Log::error('Request failed with status: ' . $res->status().$res->body());
                    throw new \Exception('Unable to process delete zone from ad-server request');
                }
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
        }catch (\Exception $exception){
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 400);
        }
    });
});

// Test route
Route::prefix('test')->middleware('auth:api')->group(function (){
   Route::get('/test-endpoint', function (){
       $api_url = 'https://ads.secure-api.net/wp-json/test-restapi/v1/test-endpoint';

       $username = 'demo-mosque-admin-digital-display';
       $password = 'Ci7L[A_ZQ04l';

//       $response = Http::withBasicAuth($username, $password)->get($api_url);
       $response = Http::withHeaders([
           'User-Agent' => 'MyCustomUserAgent/1.0',
           'Accept' => 'application/json',
           'Referer' => url()->current(),
       ])->get($api_url);
       Log::info("Response for test api {$response->status()}: {$response->body()}");
       return response()->json([
           'success' => $response->status(),
           'data' => [
               'data1', $response->body()
           ]
       ], 200);
   });

   Route::get('/test-endpoint-to-check-deployment-working', function (){
       return response()->json([
           'success' => true,
           'message' => 'deployment working successfully',
           'data' => [
               'data1', 'data2'
           ]
       ]);
   });
});

