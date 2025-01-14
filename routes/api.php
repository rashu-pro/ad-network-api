<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AdvertiserRegisteredController;
use App\Http\Controllers\Api\Auth\AdvertiserLoginController;
use App\Http\Controllers\Api\Auth\PublisherRegisteredController;
use App\Http\Controllers\Api\Auth\PublisherLoginController;
use App\Http\Controllers\Api\Admin\AssetController;
use App\Http\Controllers\Api\Admin\ZoneController;

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
Route::prefix('advertiser')->group(function (){
    Route::post('/register', [AdvertiserRegisteredController::class, 'store'])
        ->middleware('guest');
    Route::post('/login',[AdvertiserLoginController::class,'login']);
    Route::post('/get-access-token',[AdvertiserLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('advertiser')->user();
    })->middleware(['auth:advertiser','abilities:'.TokenAbility::ACCESS_API->value]);
    Route::post('/subscribe',[\App\Http\Controllers\Api\PaymentController::class,'advertiserSubscription'])->middleware('auth:advertiser');
    Route::get('/available-publishers',[\App\Http\Controllers\Api\AdvertiserOptController::class,'availablePublishers']);
    Route::post('/create-campaign',[\App\Http\Controllers\Api\AdvertiserOptController::class,'createCampaign'])->middleware('auth:advertiser');
    Route::get('/campaign/{id}/zones',[\App\Http\Controllers\Api\AdvertiserOptController::class,'getUniqueZones'])->middleware('auth:advertiser');
    Route::post('/upload-campaign-banner/{id}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'uploadCampaign'])->middleware('auth:advertiser');
    Route::post('/campaign/{id}/payment',[\App\Http\Controllers\Api\PaymentController::class,'campaignPayment'])->middleware('auth:advertiser');
    Route::post('/update-campaign/{id}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'updateCampaign'])->middleware('auth:advertiser');
    Route::get('/campaigns',[\App\Http\Controllers\Api\AdvertiserOptController::class,'allCampaigns'])->middleware('auth:advertiser');
});

Route::prefix('publisher')->group(function (){
    Route::post('/register', [PublisherRegisteredController::class, 'store'])
        ->middleware('guest');
    Route::post('/login', [PublisherLoginController::class, 'login']);
    Route::post('/get-access-token',[PublisherLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('publisher')->user();
    })->middleware(['auth:publisher','abilities:'.TokenAbility::ACCESS_API->value]);
    Route::post('/set-asset',[\App\Http\Controllers\Api\PublisherOtpController::class,'setAsset'])->middleware('auth:publisher');
    Route::post('/campaign-approval/{campaignId}',[\App\Http\Controllers\Api\PublisherOtpController::class,'publishCampaign'])->middleware('auth:publisher');
    Route::get('/{id}/assets',[\App\Http\Controllers\Api\PublisherOtpController::class,'assets']);
    Route::get('/campaigns',[\App\Http\Controllers\Api\PublisherOtpController::class,'allCampaigns'])->middleware('auth:publisher');
    Route::get('/available-zones/{asset_id}', [\App\Http\Controllers\Api\PublisherOtpController::class,'availableZones'])->middleware('auth:publisher');
});
