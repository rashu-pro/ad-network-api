<?php

namespace App\Contracts\Repositories;

use App\Models\DigitalAsset;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Collection;

interface DigitalAssetRepository
{
    public function all(): Collection;

    public function find(int $id): Model;
}
