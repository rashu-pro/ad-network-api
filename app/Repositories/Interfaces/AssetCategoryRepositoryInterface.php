<?php

namespace App\Repositories\Interfaces;
use Illuminate\Database\Eloquent\Collection;

interface AssetCategoryRepositoryInterface  extends BaseRepositoryInterface
{
    public function getAllActiveAssetCategoriesByAssetTypeId(int $assetTypeId): Collection;
}
