<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\AdvertiserRegistered;
use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public function store(Request $request)
    {
        $request->validate([
            'advertiser_name' => ['required', 'string', 'max:255'],
            'company_name' => ['string', 'max:255'],
            'advertiser_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Advertiser::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'advertiser_phone' => ['string', 'max:255'],
            'advertiser_website' => ['string', 'max:255'],
            'address' => ['string', 'max:255']
        ]);

        $advertiser = Advertiser::create([
            'advertiser_name' => $request->advertiser_name,
            'company_name' => $request->company_name,
            'advertiser_email' => $request->advertiser_email,
            'password' => Hash::make($request->string('password')),
            'advertiser_phone' => $request->advertiser_phone,
            'advertiser_website' => $request->advertiser_website,
            'address' => $request->address
        ]);

        event(new AdvertiserRegistered($advertiser));

        // Auth::login($user);

        // Return success response
        return $this->successResponse('Advertiser user created successfully.', [
            'advertiser_id' => $advertiser->id
        ]);
    }
}
