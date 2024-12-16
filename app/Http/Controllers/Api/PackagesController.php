<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class PackagesController extends Controller
{
    use ApiResponse;

    /**
     * Define middleware for the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:admin', only: ['store', 'update', 'destroy']),
            new Middleware(['auth:publisher', 'auth:advertiser', 'auth:admin'], only: ['index', 'show']),
        ];
    }

    /**
     * List all packages.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            if (Auth::guard('admin')->check()) {
                $packages = Package::with('assets')->orderBy('created_at', 'desc')->get();
            } else {
                $packages = Package::with('assets')->where('is_active', true)->orderBy('name', 'asc')->get();
            }

            return $this->successResponse('Packages retrieved successfully', $packages->toArray());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve packages', [$e->getMessage()], 500);
        }
    }

    /**
     * Show a single package.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            if (Auth::guard('admin')->check()) {
                $package = Package::with('assets')->findOrFail($id);
            } else {
                $package = Package::with('assets')->where('is_active', true)->where('id', $id)->firstOrFail();
            }

            return $this->successResponse('Package retrieved successfully', $package->toArray());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve package', [$e->getMessage()], 404);
        }
    }

    /**
     * Create a new package (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:packages,name',
            'min_attendees' => 'nullable|integer|min:0',
            'min_duration' => 'nullable|integer|min:0',
            'price' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
            'digital_asset_ids' => 'array|required',
            'digital_asset_ids.*' => 'exists:digital_assets,id'
        ]);

        try {
            $data = $request->only(['name', 'min_attendees', 'min_duration', 'price', 'is_active']);
            $package = Package::create($data);

            // Attach digital assets
            $package->assets()->attach($request->input('digital_asset_ids'));

            return $this->successResponse('Package created successfully', $package->load('assets')->toArray(), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create package', [$e->getMessage()], 500);
        }
    }

    /**
     * Update an existing package (Admin only).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:packages,name,' . $id,
            'min_attendees' => 'nullable|integer|min:0',
            'min_duration' => 'nullable|integer|min:0',
            'price' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'digital_asset_ids' => 'array|required', // Expecting an array of asset IDs
            'digital_asset_ids.*' => 'exists:digital_assets,id', // Ensure each ID exists in the digital_assets table
        ]);

        try {
            $package = Package::findOrFail($id);
            $package->update($request->only(['name', 'min_attendees', 'min_duration', 'price', 'is_active']));

            $package->assets()->sync($request->input('digital_asset_ids'));

            return $this->successResponse('Package updated successfully', $package->load('assets')->toArray());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update package', [$e->getMessage()], 500);
        }
    }

    /**
     * Delete a package (Admin only).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $package = Package::findOrFail($id);

            $package->assets()->detach();

            $package->delete();

            return $this->successResponse('Package deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete package', [$e->getMessage()], 404);
        }
    }
}
