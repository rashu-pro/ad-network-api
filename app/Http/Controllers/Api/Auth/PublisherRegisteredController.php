<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\PublisherRegistered;
use App\Facades\SecureApi;
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
        $publisher = SecureApi::createPublisher($request->all())->toArray();
        $localPublisher = Publisher::create([
            'secure_api_id' => $publisher['secure_api_id'],
            'email' => $publisher['contactInfo']['email']
        ]);

//        event(new PublisherRegistered($publisher));

        return $this->successResponse('Publisher registered successfully', [
            'secure_api_id' => $localPublisher->secure_api_id
        ]);
    }
}
