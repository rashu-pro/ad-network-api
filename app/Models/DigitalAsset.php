<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalAsset extends Model
{
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id','id');
    }
}
