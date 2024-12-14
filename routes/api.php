<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('admin')->group(function () {
    Route::post('/login',[\App\Http\Controllers\Auth\Api\Auth\AdminLoginController::class,'login']);
    Route::post('/get-access-token',[\App\Http\Controllers\Auth\Api\Auth\AdminLoginController::class,'refresh']);
    Route::get('/profile',function (Request $request) {
        return \Illuminate\Support\Facades\Auth::guard('admin')->user();
    })->middleware(['auth:admin','abilities:'.\App\Enums\TokenAbility::ACCESS_API->value]);
});


