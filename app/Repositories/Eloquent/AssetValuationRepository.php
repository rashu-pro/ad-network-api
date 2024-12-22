<?php

namespace App\Repositories\Eloquent;

use App\Models\AssetValuation;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\AssetValuationRepositoryInterface;
use \Illuminate\Database\Eloquent\Model;

class AssetValuationRepository extends BaseRepository implements AssetValuationRepositoryInterface {
    /**
     * Constructor
     *
     * @param AssetValuation $model
     */
    public function __construct(AssetValuation $model) {
        parent::__construct($model);
    }

    /**
     * Validate an asset based on its ID and population constraints.
     *
     * @param int $assetId
     * @param int $minPopulation
     * @param ?int $maxPopulation
     * @return ?Model
     */
    public function validateAsset(int $assetId, int $minPopulation, ?int $maxPopulation): ?Model
    {
        return $this->model
            ->where('asset_id', $assetId)
            ->where('min_population', '<=', $minPopulation)
            ->where(function ($query) use ($maxPopulation) {
                $query->where('max_population', '>=', $maxPopulation)
                      ->orWhereNull('max_population');
            })
            ->orderBy('min_population', 'desc')
            ->first();
    }
}

