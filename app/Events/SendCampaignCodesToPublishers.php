<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendCampaignCodesToPublishers
{
    use Dispatchable, SerializesModels;

    public $campaignMappings;

    /**
     * Create a new event instance.
     */
    public function __construct($mappings)
    {
        $this->campaignMappings = $mappings;
    }
}
