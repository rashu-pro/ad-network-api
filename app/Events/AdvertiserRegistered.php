<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdvertiserRegistered
{
    use Dispatchable, SerializesModels;

    public $advertiser;
    /**
     * Create a new event instance.
     */
    public function __construct(User $advertiser)
    {
        $this->advertiser = $advertiser;
    }
}
