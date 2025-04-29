<?php

namespace App\Listeners;

use App\Events\PublisherRegistered;
use App\Models\Publisher;
use App\Models\User;

class AddPublisherIntoAdServer
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PublisherRegistered $event): void
    {
        // Registered publisher
        $publisherRegistered = $event->publisher;
        // Call adserver add publisher api to add the publisher into adserver
        // Dummy publisher_id
        $publisher_id = 999;

        // After successful response add the publisher id into publishers table
        $publisher = User::find($publisherRegistered->id);
        $publisher->publisher_id = $publisher_id;
        $publisher->save();
    }
}
