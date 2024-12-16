<?php

namespace App\Repositories;

use App\Contracts\Repositories\DigitalAssetRepository;
use App\Models\DigitalAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserDigitalAssetRepo implements DigitalAssetRepository
{
    public function all() : Collection
    {
        return DigitalAsset::where('is_active',true)->orderBy('name')->get();
    }
    public function find(int $id): Model
    {
        return DigitalAsset::findOrFail($id);
    }
}
