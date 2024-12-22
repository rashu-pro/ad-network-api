<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use App\Http\Controllers\Api\Auth\AdvertiserLoginController;
use App\Http\Controllers\Api\Auth\AdvertiserRegisteredController;
use App\Http\Controllers\Api\Auth\PublisherLoginController;
use App\Http\Controllers\Api\Auth\PublisherRegisteredController;
use App\Http\Controllers\Api\DigitalAssetsController;
use App\Http\Controllers\Api\PackagesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('admin')->group(function () {
    Route::post('/login',[AdminLoginController::class,'login']);
    Route::post('/get-access-token',[AdminLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('admin')->user();
    })->middleware(['auth:admin','abilities:'.TokenAbility::ACCESS_API->value]);
});
Route::prefix('advertiser')->group(function (){
    Route::post('/register', [AdvertiserRegisteredController::class, 'store'])
        ->middleware('guest');
    Route::post('/login',[AdvertiserLoginController::class,'login']);
    Route::post('/get-access-token',[AdvertiserLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('advertiser')->user();
    })->middleware(['auth:advertiser','abilities:'.TokenAbility::ACCESS_API->value]);
    Route::post('/create-campaign',[\App\Http\Controllers\Api\AdvertiserOptController::class,'createCampaign']);
    Route::post('/update-campaign/{id}',[\App\Http\Controllers\Api\AdvertiserOptController::class,'updateCampaign']);
    Route::get('/campaigns',[\App\Http\Controllers\Api\AdvertiserOptController::class,'allCampaigns']);
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
    Route::get('/assets',[\App\Http\Controllers\Api\PublisherOtpController::class,'assets'])->middleware('auth:publisher');
    Route::get('/campaigns',[\App\Http\Controllers\Api\PublisherOtpController::class,'allCampaigns'])->middleware('auth:publisher');
});
