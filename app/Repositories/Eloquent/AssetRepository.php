<?php

namespace App\Repositories\Eloquent;

use App\Models\Asset;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AssetRepository extends BaseRepository implements AssetRepositoryInterface {
    public function __construct(Asset $model) {
        parent::__construct($model);
    }
    public function getActiveAssets(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    public function setPublisherAssets($assetId, $publisherId): bool
    {
        $asset = $this->find($assetId);
        if (!$asset){
            throw new ModelNotFoundException('Asset not found');
        }
    }
}
