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
use OpenApi\Attributes as OA;
class AdvertiserRegisteredController extends Controller
{
    use ApiResponse;

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    #[OA\Post(
        path: "/api/advertiser/register",
        summary: "Register a new advertiser",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["first_name", "last_name", "email", "password", "password_confirmation"],
                    properties: [
                        new OA\Property(property: "first_name", description: "First name of the advertiser", type: "string", example: "John"),
                        new OA\Property(property: "last_name", description: "Last name of the advertiser", type: "string", example: "Doe"),
                        new OA\Property(property: "business_name", description: "Business name of the advertiser", type: "string", example: "Tech Corp", nullable: true),
                        new OA\Property(property: "email", description: "Email address of the advertiser", type: "string", format: "email", example: "john.doe@example.com"),
                        new OA\Property(property: "password", description: "Password for the advertiser account", type: "string", format: "password", example: "password123"),
                        new OA\Property(property: "password_confirmation", description: "Password confirmation for verification", type: "string", format: "password", example: "password123"),
                        new OA\Property(property: "advertiser_phone", description: "Phone number of the advertiser", type: "string", example: "+1234567890", nullable: true),
                        new OA\Property(property: "advertiser_website", description: "Website of the advertiser", type: "string", example: "https://example.com", nullable: true),
                        new OA\Property(property: "address", description: "Address of the advertiser", type: "string", example: "123 Main Street, Springfield", nullable: true),
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Advertiser"],
        responses: [
            new OA\Response(response: 201, description: "Advertiser registered successfully",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Advertiser user created successfully."),
                        new OA\Property(property: "data", properties: [
                            new OA\Property(property: "advertiser_id", type: "integer", example: 1),
                        ],
                            type: "object"
                        )
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation Error"),
                        new OA\Property(property: "errors", type: "object"),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "An unexpected error occurred"),
                        new OA\Property(property: "errors", type: "object", nullable: true),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]

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
