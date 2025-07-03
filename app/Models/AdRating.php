<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdRating extends Model
{
    protected $fillable = [
        'campaign_mapping_id',
        'advertiser_id',
        'rating',
        'comment',
    ];

    public function mapping()
    {
        return $this->belongsTo(CampaignMapping::class, 'campaign_mapping_id');
    }

    public function advertiser()
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }
}
