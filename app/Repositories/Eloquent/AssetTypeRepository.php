<?php

namespace App\Repositories\Eloquent;

use App\Models\AssetType;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\AssetTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssetTypeRepository extends BaseRepository implements AssetTypeRepositoryInterface
{
    public function __construct(AssetType $model){
        parent::__construct($model);
    }

    public function getAllActiveAssetTypes(): Collection
    {
        return $this->model->where('status', true)
                            ->orderBy('name')
                            ->get();
    }
}
