<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublisherAssetImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'publisher_asset_id',
        'image_url',
    ];

    /**
     * Get the publisher asset that owns the image.
     */
    public function asset()
    {
        return $this->belongsTo(PublisherAsset::class, 'publisher_asset_id');
    }
}
