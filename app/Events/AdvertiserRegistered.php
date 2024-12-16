<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdvertiserRegistered
{
    use Dispatchable, SerializesModels;

    public $advertiser;
    /**
     * Create a new event instance.
     */
    public function __construct($advertiser)
    {
        $this->advertiser = $advertiser;
    }
}
