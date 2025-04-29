<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignPayment extends Model
{
    protected $fillable = [
        'advertiser_id',
        'campaign_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function advertiser()
    {
        return $this->belongsTo(User::class);
    }
}
