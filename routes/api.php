<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RegisteredUserController;

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

Route::post('/advertiser/register', [\App\Http\Controllers\Api\Auth\AdvertiserRegisteredController::class, 'store'])
    ->middleware('guest');


