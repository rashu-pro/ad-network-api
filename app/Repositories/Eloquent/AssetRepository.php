<?php

namespace App\Repositories\Eloquent;

use App\Models\Asset;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class AssetRepository extends BaseRepository implements AssetRepositoryInterface {
    public function __construct(Asset $model) {
        parent::__construct($model);
    }
    public function getActiveAssets(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }
}
