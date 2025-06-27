<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PublisherAsset extends Model
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'publisher_assets';

    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * The publisher that this asset belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function publisher()
    {
        return $this->belongsTo(User::class,'publisher_id','id');
    }

    /**
     * The asset that belongs to the publisher.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Define media collection(s) for Spatie.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('feature')->singleFile(); // Only one feature image
        $this->addMediaCollection('gallery');               // Multiple gallery images
    }

}
