<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
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
    public function allZones(): JsonResponse
    {
        $allZones = $this->adminService->allZones();
        return $this->successResponse('All the zone list', $allZones);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function viewZone(int $id): JsonResponse
    {
        $zone = $this->adminService->viewZone($id);
        return $this->successResponse('zone found', $zone);
    }

    /**
     * @param int $assetId
     * @return JsonResponse
     */
    public function zonesByAssetId(int $assetId): JsonResponse
    {
        $zones = $this->adminService->allZonesByAssetId($assetId);
        return $this->successResponse('zones found', $zones);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createZone(Request $request): JsonResponse
    {
        $data = $request->only([
            'asset_id', 'zone_name', 'width', 'height', 'type_id'
        ]);
        $zone = $this->adminService->createZone($data);
        return $this->successResponse('Zone created successfully', $zone);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateZone(Request $request, $id): JsonResponse
    {
        $data = $request->only([
            'asset_id', 'zone_name', 'width', 'height', 'type_id'
        ]);
        $updated = $this->adminService->updateZone($id, $data);

        if ($updated) {
            return $this->successResponse('Zone updated successfully.');
        }

        return $this->errorResponse('Zone not found or update failed.', [], 404);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function deleteZone(int $id): JsonResponse
    {
        $status = $this->adminService->deleteZone($id);
        if($status){
            return $this->successResponse('Zone deleted successfully', (array)$status);
        }
        return $this->errorResponse('No zone found for the given id');
    }
}
