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
        $allAssets = $this->adminService->viewAllAssets();
        return $this->successResponse('All the asset list', (array)$allAssets);
    }

    /**
     * @return JsonResponse
     */
    public function allActiveAssets(): JsonResponse
    {
        $allAssets = $this->adminService->viewAllActiveAssets();
        return $this->successResponse('All the active asset list', (array)$allAssets);
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
            $data = [
                'asset_id' => $asset->id,
                'has_url' => $request->has_url,
                'min_population' => $request->min_population,
                'max_population' => $request->max_population,
                'min_duration_in_hour' => $request->min_duration_in_hour,
                'max_price_per_hour' => $request->max_price_per_hour
            ];
            $assetValuation = $this->adminService->createAssetValuation($data);
            return $this->successResponse('Asset created successfully', (array)$asset);
        }catch (Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

    public function deleteAsset(int $id): JsonResponse
    {
        try{
            $status = $this->adminService->deleteAsset($id);
            return $this->successResponse('Asset deleted successfully', (array)$status);
        }catch (Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }

}
