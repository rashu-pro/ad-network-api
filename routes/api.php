<?php

use App\Enums\CampaignStatus;
use App\Enums\PublisherCampaignStatus;
use App\Enums\RolesEnum;
use App\Enums\TokenAbility;
use App\Events\CampaignPublished;
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
        return \App\Http\Resources\CampaignResource::collection(User::where('secure_api_id', $companyKey)->first()->publisherCampaigns ?? []);
    });
    Route::post('/campaign/{slug}', [\App\Http\Controllers\Api\External\PublisherCampaignController::class,'storeCampaign']);
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

