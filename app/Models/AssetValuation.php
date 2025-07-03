<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetValuation extends Model
{
    protected $table = 'asset_valuations';

    protected $fillable = [
        'asset_id',
        'has_url',
        'min_population',
        'max_population',
        'min_duration_in_hour',
        'max_price_per_hour',
    ];

    /**
     * Get the asset that owns the AssetValuation
     *
     * @return BelongsTo
     */
    public function asset() : BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
