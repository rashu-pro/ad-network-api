<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\RolesEnum;
use App\Events\AdvertiserRegistered;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Models\Publisher;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRegisteredController extends Controller
{
    use ApiResponse;

    public function userCreate(Request $request)
    {
        $secureApiUser = SecureApi::createUser($request->all())->toArray();
        $user = User::create([
            'secure_api_id' => $secureApiUser['secure_api_id'],
            'email' => $secureApiUser['contactInfo']['email']
        ]);

        if($request->isAdvertiser){
            $advertiserRole = Role::findByName(RolesEnum::ADVERTISER->value);
            $user->assignRole($advertiserRole);
            event(new AdvertiserRegistered($user));
        }
        if($request->isAdPublisher){
            $publisherRole = Role::findByName(RolesEnum::PUBLISHER->value);
            $user->assignRole($publisherRole);
        }
        return $this->successResponse('User created successfully.', [
            'secure_api_id' => $user['secure_api_id'],
        ]);
    }

//    /**
//     * Handle an incoming registration request.
//     *
//     * @throws \Illuminate\Validation\ValidationException
//     */
//    public function store(Request $request): JsonResponse
//    {
//        $advertiser = SecureApi::createUser($request->all())->toArray();
//        $localAdvertiser = Advertiser::create([
//            'secure_api_id' => $advertiser['secure_api_id'],
//            'email' => $advertiser['contactInfo']['email']
//        ]);
//
//        // Return success response
//        return $this->successResponse('Advertiser user created successfully.', [
//            'secure_api_id' => $localAdvertiser->secure_api_id
//        ]);
//    }
}
