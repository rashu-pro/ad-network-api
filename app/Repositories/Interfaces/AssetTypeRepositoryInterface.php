<?php

namespace App\Repositories\Interfaces;
use Illuminate\Database\Eloquent\Collection;

interface AssetTypeRepositoryInterface  extends BaseRepositoryInterface
{
    public function getAllActiveAssetTypes(): Collection;
}
