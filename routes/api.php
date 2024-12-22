<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use App\Http\Controllers\Api\DigitalAssetsController;
use App\Http\Controllers\Api\PackagesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AdvertiserRegisteredController;
use App\Http\Controllers\Api\Auth\AdvertiserLoginController;
use App\Http\Controllers\Api\Auth\PublisherRegisteredController;
use App\Http\Controllers\Api\Auth\PublisherLoginController;
use App\Http\Controllers\Api\Admin\AssetController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('admin')->group(function () {
    Route::post('/login',[AdminLoginController::class,'login']);
    Route::post('/get-access-token',[AdminLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('admin')->user();
    })->middleware(['auth:admin','abilities:'.TokenAbility::ACCESS_API->value]);
    Route::post('/assets/add', [AssetController::class, 'createAsset'])
        ->middleware(['auth:admin','abilities:'.TokenAbility::ACCESS_API->value]);
});
Route::prefix('advertiser')->group(function (){
    Route::post('/register', [AdvertiserRegisteredController::class, 'store'])
        ->middleware('guest');
    Route::post('/login',[AdvertiserLoginController::class,'login']);
    Route::post('/get-access-token',[AdvertiserLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('advertiser')->user();
    })->middleware(['auth:advertiser','abilities:'.TokenAbility::ACCESS_API->value]);
});

Route::prefix('publisher')->group(function (){
    Route::post('/register', [PublisherRegisteredController::class, 'store'])
        ->middleware('guest');
    Route::post('/login', [PublisherLoginController::class, 'login']);
    Route::post('/get-access-token',[PublisherLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return Auth::guard('publisher')->user();
    })->middleware(['auth:publisher','abilities:'.TokenAbility::ACCESS_API->value]);
});
