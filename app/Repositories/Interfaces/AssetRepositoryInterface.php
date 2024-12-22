<?php

namespace App\Repositories\Interfaces;

interface AssetRepositoryInterface extends BaseRepositoryInterface {
    /**
     * Get all active assets.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActiveAssets(): \Illuminate\Support\Collection;
}
