<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'long',
        'lat',
        'address',
        'city',
        'zip_code',
        'state',
        'country',
    ];

    /**
     * Get the owning user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user()
    {
        return $this->morphTo();
    }
}
