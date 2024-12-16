<?php

namespace App\Models\Pivot;

use App\Models\DigitalAsset;
use App\Models\Package;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PackageAsset extends Pivot
{
    public $incrementing = true;

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
    public function asset()
    {
        return $this->belongsTo(DigitalAsset::class);
    }
}
