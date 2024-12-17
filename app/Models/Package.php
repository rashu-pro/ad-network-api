<?php

namespace App\Models;

use App\Models\Pivot\PackageAsset;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    public function assets()
    {
        return $this->belongsToMany(DigitalAsset::class,'package_asset')->using(PackageAsset::class)->withTimestamps();
    }
}
