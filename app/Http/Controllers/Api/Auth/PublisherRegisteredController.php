<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\PublisherRegistered;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Models\Publisher;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PublisherRegisteredController extends Controller
{
    use ApiResponse;
    /**
     * Register a publisher
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/publishers/register",
        summary: "Register a new publisher",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name", "email", "website_url"],
                    properties: [
                        new OA\Property(property: "guid", description: "GUID of the publisher", type: "string", example: "123e4567-e89b-12d3-a456-426614174000", nullable: true),
                        new OA\Property(property: "name", description: "Name of the publisher", type: "string", example: "John Doe"),
                        new OA\Property(property: "email", description: "Email address of the publisher", type: "string", format: "email", example: "john.doe@example.com"),
                        new OA\Property(property: "company_name", description: "Company name of the publisher", type: "string", example: "Tech Solutions Ltd.", nullable: true),
                        new OA\Property(property: "company_address", description: "Address of the company", type: "string", example: "123 Business St., Springfield", nullable: true),
                        new OA\Property(property: "website_url", description: "Website URL of the publisher", type: "string", format: "url", example: "https://www.techsolutions.com"),
                        new OA\Property(property: "latitude", description: "Latitude of the publisher's location", type: "number", format: "float", example: 40.712776, nullable: true),
                        new OA\Property(property: "longitude", description: "Longitude of the publisher's location", type: "number", format: "float", example: -74.005974, nullable: true),
                        new OA\Property(property: "logo_url", description: "URL of the publisher's logo", type: "string", format: "url", example: "https://www.techsolutions.com/logo.png", nullable: true),
                        new OA\Property(property: "package_id", description: "Package ID assigned to the publisher", type: "integer", example: 1, nullable: true),
                        new OA\Property(property: "publisher_id", description: "ID of the parent publisher (if any)", type: "integer", example: 10, nullable: true),
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Publisher"],
        responses: [
            new OA\Response(response: 201, description: "Publisher registered successfully",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Publisher registered successfully."),
                        new OA\Property(property: "data", description: "Publisher details", properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "John Doe"),
                            new OA\Property(property: "email", type: "string", example: "john.doe@example.com"),
                            new OA\Property(property: "company_name", type: "string", example: "Tech Solutions Ltd."),
                            new OA\Property(property: "website_url", type: "string", example: "https://www.techsolutions.com"),
                        ], type: "object"),
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
                        new OA\Property(property: "message", type: "string", example: "Failed to register publisher"),
                        new OA\Property(property: "errors", type: "array", items: new OA\Items(type: "string"), example: ["An unexpected error occurred"]),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]
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
