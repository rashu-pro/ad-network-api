<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class DigitalAssetsController extends Controller
{
    use ApiResponse;

    /**
     * Define middleware for the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:admin', only: ['store', 'destroy']),
            new Middleware(['auth:publisher','auth:advertiser','auth:admin'], only: ['index', 'show']),
        ];
    }

    /**
     * List all digital assets.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            if(Auth::guard('admin')->check()){
                $digitalAssets = DigitalAsset::orderBy('created_at','desc')->get();
                return $this->successResponse('Digital assets retrieved successfully', $digitalAssets->toArray());
            }
            $digitalAssets = DigitalAsset::where('is_active', true)->orderBy('name','asc')->get();
            return $this->successResponse('Digital assets retrieved successfully', $digitalAssets->toArray());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve digital assets', [$e->getMessage()], 500);
        }
    }

    /**
     * Show a single digital asset.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            if(Auth::guard('admin')->check()){
                $digitalAsset = DigitalAsset::findOrFail($id);
            }else{
                $digitalAsset = DigitalAsset::where('is_active',true)->where('id',$id)->firstOrFail();
            }

            return $this->successResponse('Digital asset retrieved successfully', $digitalAsset->toArray());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve digital asset', [$e->getMessage()], 404);
        }
    }

    /**
     * Create a new digital asset (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:digital_assets,name',
            'is_active' => 'required|boolean',
        ]);

        try {
            $data = $request->only(['name', 'creator_id', 'is_active']);
            $data['creator_id'] = auth()->id();
            $digitalAsset = DigitalAsset::create($data);

            return $this->successResponse('Digital asset created successfully', $digitalAsset->toArray(), 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: 'Failed to create digital asset', errors: [$e->getMessage()], status: 500);
        }
    }

    /**
     * Delete a digital asset (Admin only).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $digitalAsset = DigitalAsset::findOrFail($id);

            $digitalAsset->delete();

            return $this->successResponse('Digital asset deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete digital asset', [$e->getMessage()], 404);
        }
    }
}
