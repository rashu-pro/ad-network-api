<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetTypeResource;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use OpenApi\Attributes as OA;

class AssetTypeController extends Controller
{
    use ApiResponse;

    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/admin/asset-types",
        summary: "All Active Asset Types",
        security: [
            ["bearerAuth" => []],
            ["sanctum" => []],
        ],
        tags: ["Admin"],
        responses: [
            new OA\Response(response: 200, description: "List of active asset types",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "All the active asset type list"),
                            new OA\Property(
                                property: "data",
                                description: "List of active asset types",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "Billboard"),
                                        new OA\Property(property: "description", type: "string", example: "Outdoor advertisement board"),
                                        new OA\Property(property: "status", type: "boolean", example: true)
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
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Validation Error"),
                            new OA\Property(property: "errors", type: "object")
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Server Error"),
                            new OA\Property(property: "errors", type: "object", nullable: true)
                        ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function getAllActiveAssetTypes(): JsonResponse
    {
        $assetTypes = $this->adminService->getAllActiveAssetTypes();

        $assetTypesResource = AssetTypeResource::collection($assetTypes);

        return $this->successResponse('All the active asset type list', $assetTypesResource);
    }

}
