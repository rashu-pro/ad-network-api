<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublisherRegistered
{
    use Dispatchable, SerializesModels;

    public $publisher;

    /**
     * Create a new event instance.
     */
    public function __construct($publisher)
    {
        $this->publisher = $publisher;
    }
}
