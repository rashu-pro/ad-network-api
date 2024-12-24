<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\AdvertiserRegistered;
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
        $validatedData = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'business_name' => ['string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Advertiser::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'advertiser_phone' => ['string', 'max:255'],
            'advertiser_website' => ['string', 'max:255'],
            'address' => ['string', 'max:255']
        ]);

        $advertiser = Advertiser::create($validatedData);

        event(new AdvertiserRegistered($advertiser));

        // Auth::login($user);

        // Return success response
        return $this->successResponse('Advertiser user created successfully.', [
            'advertiser_id' => $advertiser->id
        ]);
    }
}
