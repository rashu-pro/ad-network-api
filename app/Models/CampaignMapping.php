<?php

namespace App\Models;

use App\Enums\PublisherCampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CampaignMapping extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded =[
        'id', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => PublisherCampaignStatus::class,
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

    public function publisherZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'publisher_zone_id', 'id');
    }

    public function registerMediaCollections(): void
    {

        $this
            ->addMediaCollection('banner')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg','image/jpg','image/png']);
    }
}
