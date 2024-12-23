<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\PaymentStatus;
use App\Enums\CampaignStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Campaign extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'advertiser_id',
        'advertiser_adserver_id',
        'campaign_name',
        'target_url',
        'payment_status',
        'status',
        'note',
        'is_draft',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'payment_status' => PaymentStatus::class,
        'status' => CampaignStatus::class,
    ];


    /**
     * Get the transactions for the campaign.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the campaign mappings for the campaign.
     *
     * @return HasMany
     */
    public function campaignMappings(): HasMany
    {
        return $this->hasMany(CampaignMapping::class);
    }

    public function mappings()
    {
        return $this->hasMany(CampaignMapping::class);
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class, 'advertiser_id', 'id');
    }

}
