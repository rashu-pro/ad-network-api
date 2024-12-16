<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\Auth\AdminLoginController;
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

Route::prefix('digital-assets')->group(function () {
    Route::get('/', [DigitalAssetsController::class, 'index']);
    Route::get('/{id}', [DigitalAssetsController::class, 'show']);
    Route::post('/', [DigitalAssetsController::class, 'store']);
    Route::post('delete/{id}', [DigitalAssetsController::class, 'destroy']);
});
Route::prefix('packages')->group(function () {
    Route::get('/', [PackagesController::class, 'index']);
    Route::get('/{id}', [PackagesController::class, 'show']);
    Route::post('/', [PackagesController::class, 'store']);
    Route::post('update/{id}', [PackagesController::class, 'update']);
    Route::post('delete/{id}', [PackagesController::class, 'destroy']);
});
