<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\AdvertiserRegistered;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Models\Publisher;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class AdvertiserRegisteredController extends Controller
{
    use ApiResponse;

    public function userCreate(Request $request)
    {
        $user = SecureApi::createUser($request->all())->toArray();
        if($user['isAdvertiser']){
            $localAdvertiser = Advertiser::create([
                'secure_api_id' => $user['secure_api_id'],
                'email' => $user['contactInfo']['email']
                ]);
        }
        if($user['isPublisher']){
            $localPublisher = Publisher::create([
                'secure_api_id' => $user['secure_api_id'],
                'email' => $user['contactInfo']['email']
            ]);
        }
        return $this->successResponse('User created successfully.', [
            'secure_api_id' => $user['secure_api_id'],
        ]);
    }

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
