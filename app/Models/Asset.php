<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * Relationship: Asset belongs to an Asset Type
     */
    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    /**
     * Relationship: Asset belongs to an Asset Category
     */
    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    /**
     * The asset valuations.
     *
     * @return HasMany
     */
    public function valuations(): HasMany
    {
        return $this->hasMany(AssetValuation::class, 'asset_id', 'id');
    }

    /**
     * The publishers that this asset belongs to.
     *
     * @return HasMany
     */
    public function publishers(): HasMany
    {
        return $this->hasMany(PublisherAsset::class, 'asset_id', 'id');
    }

    /**
     * Zones under this asset.
     *
     * @return HasMany
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'asset_id', 'id');
    }
}
