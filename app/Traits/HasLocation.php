<?php

namespace App\Traits;

use App\Models\UserLocation;

trait HasLocation
{
    /**
     * Get the location associated with the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function location()
    {
        return $this->morphOne(UserLocation::class, 'user');
    }
}