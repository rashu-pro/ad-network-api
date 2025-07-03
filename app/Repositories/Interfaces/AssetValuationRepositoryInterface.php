<?php

namespace App\Repositories\Interfaces;

interface AssetValuationRepositoryInterface extends BaseRepositoryInterface {
    /**
     * Validate an asset based on its ID and population constraints.
     *
     * @param int $assetId
     * @param int $minPopulation
     * @param ?int $maxPopulation
     * @return ?\Illuminate\Database\Eloquent\Model
     */
    public function validateAsset(int $assetId, int $minPopulation, ?int $maxPopulation): ?\Illuminate\Database\Eloquent\Model;
}
