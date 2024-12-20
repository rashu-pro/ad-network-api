<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'advertiser_id',
        'campaign_id',
        'publisher_id',
        'publisher_asset_id',
        'start_date',
        'end_date',
        'calculated_price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Get the advertiser that owns the campaign mapping.
     *
     * @return BelongsTo
     */
    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }

    /**
     * Get the campaign that owns the campaign mapping.
     *
     * @return BelongsTo
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the publisher that owns the campaign mapping.
     *
     * @return BelongsTo
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    /**
     * Get the publisher asset that owns the campaign mapping.
     *
     * @return BelongsTo
     */
    public function publisherAsset(): BelongsTo
    {
        return $this->belongsTo(PublisherAsset::class);
    }
}