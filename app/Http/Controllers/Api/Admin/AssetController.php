<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use OpenApi\Attributes as OA;
class AssetController extends Controller
{
    use ApiResponse;

    protected $adminService;

    /**
     * @param AdminService $adminService
     */
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "/api/admin/assets",
        summary: "All Assets",
        security: [
            ["bearerAuth" => []], // For JWT bearer tokens
            ["sanctum" => []],    // For Sanctum API keys
        ],
        tags: ["Admin"],
        responses: [
            new OA\Response(response: 200, description: "All assets",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: true),
                        new OA\Property(property: 'message', type: "string", example: "Logged in successfully."),
                        new OA\Property(
                            property: 'data',
                            description: "List of assets",  // Indicate that 'data' is an array
                            type: "array",
                            items: new OA\Items( // Define the schema for the items in the array
                                properties: [
                                    new OA\Property(property: 'id', type: "integer", example: 1),
                                    new OA\Property(property: 'name', type: "string", example: "Conference Room A"),
                                    new OA\Property(
                                        property: 'type',
                                        description: "Type of asset, either 'online' or 'offline'",
                                        type: "string",
                                        enum: ["online", "offline"],
                                        example: "online"
                                    ),
                                    new OA\Property(property: 'is_active', type: "boolean", example: true),
                                ],
                                type: "object"
                            )
                        )
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Validation Error"),
                        new OA\Property(property: 'errors', type: "object"),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Server Error"),
                        new OA\Property(property: 'errors', type: "object", nullable: true),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function allAssets(): JsonResponse
    {
        $allAssets = $this->adminService->viewAllAssets();
        return $this->successResponse('All the asset list', (array)$allAssets);
    }

    /**
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/admin/assets/active",
        summary: "All Assets",
        security: [
            ["bearerAuth" => []], // For JWT bearer tokens
            ["sanctum" => []],    // For Sanctum API keys
        ],
        tags: ["Admin"],
        responses: [
            new OA\Response(response: 200, description: "All active assets",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: true),
                        new OA\Property(property: 'message', type: "string", example: "Logged in successfully."),
                        new OA\Property(
                            property: 'data',
                            description: "List of assets",  // Indicate that 'data' is an array
                            type: "array",
                            items: new OA\Items( // Define the schema for the items in the array
                                properties: [
                                    new OA\Property(property: 'id', type: "integer", example: 1),
                                    new OA\Property(property: 'name', type: "string", example: "Conference Room A"),
                                    new OA\Property(
                                        property: 'type',
                                        description: "Type of asset, either 'online' or 'offline'",
                                        type: "string",
                                        enum: ["online", "offline"],
                                        example: "online"
                                    ),
                                    new OA\Property(property: 'is_active', type: "boolean", example: true),
                                ],
                                type: "object"
                            )
                        )

                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Validation Error"),
                        new OA\Property(property: 'errors', type: "object"),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Server Error"),
                        new OA\Property(property: 'errors', type: "object", nullable: true),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function allActiveAssets(): JsonResponse
    {
        $allAssets = $this->adminService->viewAllActiveAssets();
        return $this->successResponse('All the active asset list', (array)$allAssets);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/admin/assets/add",
        summary: "Create an Asset",
        security: [
            ["bearerAuth" => []], // For JWT bearer tokens
            ["sanctum" => []],    // For Sanctum API keys
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', description: "Name of the asset", type: "string", example: "Conference Room A"),
                        new OA\Property(property: 'type', description: "Type of asset", type: "string", enum: ["online", "offline"], example: "online"),
                        new OA\Property(property: 'is_active', description: "Whether the asset is active", type: "boolean", example: true),
                        new OA\Property(property: 'has_url', description: "Whether the asset has a URL", type: "boolean", example: true),
                        new OA\Property(property: 'min_population', description: "Minimum population capacity", type: "integer", example: 10),
                        new OA\Property(property: 'max_population', description: "Maximum population capacity", type: "integer", example: 100),
                        new OA\Property(property: 'min_duration_in_hour', description: "Minimum duration in hours", type: "integer", example: 1),
                        new OA\Property(property: 'max_price_per_hour', description: "Maximum price per hour", type: "integer", example: 500),
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Admin"],
        responses: [
            new OA\Response(response: 200, description: "Asset created successfully",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: true),
                        new OA\Property(property: 'message', type: "string", example: "Asset created successfully."),
                        new OA\Property(property: 'data', properties: [

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
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Validation Error"),
                        new OA\Property(property: 'errors', type: "object"),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Server Error"),
                        new OA\Property(property: 'errors', type: "object", nullable: true),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]

    public function createAsset(Request $request): JsonResponse
    {
        try{
            $data = $request->only([
                'name', 'type', 'is_active'
            ]);
            $asset = $this->adminService->createAsset($data);
            $data = [
                'asset_id' => $asset->id,
                'has_url' => $request->has_url,
                'min_population' => $request->min_population,
                'max_population' => $request->max_population,
                'min_duration_in_hour' => $request->min_duration_in_hour,
                'max_price_per_hour' => $request->max_price_per_hour
            ];
            $assetValuation = $this->adminService->createAssetValuation($data);
            return $this->successResponse('Asset created successfully');
        }catch (Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

    #[OA\Post(
        path: "/api/admin/assets/delete/{id}",
        summary: "Delete an Asset",
        security: [
            ["bearerAuth" => []], // For JWT bearer tokens
            ["sanctum" => []],    // For Sanctum API keys
        ],
        tags: ["Admin"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID of the asset to be deleted",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Asset deleted successfully",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: true),
                        new OA\Property(property: 'message', type: "string", example: "Asset deleted successfully."),
                        new OA\Property(property: 'data', type: "object", example: null, nullable: true),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 404, description: "Asset not found",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "No asset found for the given id"),
                        new OA\Property(property: 'errors', type: "object", example: null, nullable: true),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "An unexpected error occurred"),
                        new OA\Property(property: 'errors', type: "object", example: null, nullable: true),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function deleteAsset(int $id): JsonResponse
    {
        try{
            $status = $this->adminService->deleteAsset($id);
            if($status){
                return $this->successResponse('Asset deleted successfully');
            }
            return $this->errorResponse('No asset found for the given id');
        }catch (Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

}
