<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetCategoryResource;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use OpenApi\Attributes as OA;

class AssetCategoryController extends Controller
{
    use ApiResponse;

    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * @param int $assetTypeId
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/admin/asset-categories/{assetTypeId}/active",
        summary: "All Active Asset Categories by Asset Type",
        security: [
            ["bearerAuth" => []],
            ["sanctum" => []],
        ],
        tags: ["Admin"],
        parameters: [
            new OA\Parameter(
                name: "assetTypeId",
                description: "ID of the asset type",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "List of active asset categories by type",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "All the active asset type list by Asset Type"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 101),
                                        new OA\Property(property: "name", type: "string", example: "LED Screen"),
                                        new OA\Property(property: "description", type: "string", example: "Large LED advertising screen"),
                                        new OA\Property(property: "asset_type_id", type: "integer", example: 1),
                                        new OA\Property(property: "status", type: "boolean", example: true),
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
    public function getAllActiveAssetCategoriesByAssetTypeId(int $assetTypeId): JsonResponse
    {
        $assetCategories = $this->adminService->getAllActiveAssetCategoriesByAssetTypeId($assetTypeId);

        $assetCategoriesResource = AssetCategoryResource::collection($assetCategories);

        return $this->successResponse('All the active asset type list by Asset Type', $assetCategoriesResource);
    }

}
