<?php

namespace App\Repositories;

use App\Contracts\Repositories\DigitalAssetRepository;
use App\Models\DigitalAsset;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AdminDigitalAssetRepo implements DigitalAssetRepository
{
    public function all() : Collection
    {
        return DigitalAsset::orderBy('created_at', 'desc')->get();
    }
    public function find(int $id): Model
    {
        return DigitalAsset::findOrFail($id);
    }

    public function create(array $data): Model
    {
        $asset = new DigitalAsset();
        $asset->fill($data);
        $asset->save();
        return $asset;
    }

    public function update(int $id, array $data): Model
    {
        $asset = $this->findById($id);
        $asset->fill($data);
        $asset->save();
        return $asset;
    }
    public function delete(int $id): bool
    {
        $asset = $this->findById($id);
        return $asset->delete();
    }
}
