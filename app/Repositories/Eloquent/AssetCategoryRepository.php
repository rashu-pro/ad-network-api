<?php

namespace App\Repositories\Eloquent;

use App\Models\AssetCategory;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\AssetCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssetCategoryRepository extends BaseRepository implements AssetCategoryRepositoryInterface
{
    public function __construct(AssetCategory $model){
        parent::__construct($model);
    }

    public function getAllActiveAssetCategoriesByAssetTypeId(int $assetTypeId): Collection
    {
        return $this->model
            ->with('assetType')
            ->where('status', true)
            ->where('asset_type_id', $assetTypeId)
            ->orderBy('name')
            ->get();
    }

}
