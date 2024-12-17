<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\PublisherRegistered;
use App\Http\Controllers\Controller;
use App\Models\Publisher;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublisherRegisteredController extends Controller
{
    use ApiResponse;
    /**
     * Register a publisher
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'guid' => 'string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:publishers,email',
            'company_name' => 'string|max:255',
            'company_address' => 'string|max:255',
            'website_url' => 'required|url:http,https',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'logo_url' => 'url:http,https',
            'package_id' => 'integer',
            'publisher_id' => 'integer'
        ]);

        try {
            $publisher = Publisher::create($validatedData);

            event(new PublisherRegistered($publisher));

            return $this->successResponse('Publisher registered successfully', $publisher->toArray(), 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: 'Failed to register publisher', errors: [$e->getMessage()], status: 500);
        }
    }
}
