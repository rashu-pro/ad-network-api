<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignMappingPauseHistory extends Model
{
    protected $fillable = ['campaign_mapping_id', 'paused_at', 'resumed_at'];

    public function campaignMapping()
    {
        return $this->belongsTo(CampaignMapping::class);
    }
}
