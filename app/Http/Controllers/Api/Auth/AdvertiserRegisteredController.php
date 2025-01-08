<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\AdvertiserRegistered;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdvertiserRegisteredController extends Controller
{
    use ApiResponse;

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $advertiser = SecureApi::createAdvertiser($request->all())->toArray();
        $localAdvertiser = Advertiser::create([
            'secure_api_id' => $advertiser['secure_api_id'],
            'email' => $advertiser['contactInfo']['email']
        ]);

        // Return success response
        return $this->successResponse('Advertiser user created successfully.', [
            'secure_api_id' => $localAdvertiser->secure_api_id
        ]);
    }
}
