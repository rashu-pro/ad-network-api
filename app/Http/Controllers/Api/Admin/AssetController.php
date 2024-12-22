<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

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
    public function allAssets(): JsonResponse
    {
        return $this->successResponse('All the asset list', $this->adminService->viewAllAssets());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createAsset(Request $request): JsonResponse
    {
        try{
            $data = $request->only([
                'name', 'type', 'is_active'
            ]);
            $asset = $this->adminService->createAsset($data);
            return $this->successResponse('Asset created successfully', (array)$asset);
        }catch (Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

}
