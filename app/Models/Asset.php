<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * The asset valuations.
     *
     * @return HasMany
     */
    public function valuations() : HasMany
    {
        return $this->hasMany(AssetValuation::class, 'asset_id', 'id');
    }

    /**
     * The publishers that this asset belongs to.
     *
     * @return HasMany
     */
    public function publishers() : HasMany
    {
        return $this->hasMany(PublisherAsset::class, 'asset_id', 'id');
    }
}
