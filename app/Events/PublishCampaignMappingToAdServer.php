<?php

namespace App\Events;

use App\Models\CampaignMapping;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublishCampaignMappingToAdServer
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CampaignMapping $mapping;

    /**
     * Create a new event instance.
     */
    public function __construct(CampaignMapping $mapping)
    {
        $this->mapping = $mapping;
    }

}
