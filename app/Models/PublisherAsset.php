<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublisherAsset extends Model
{
    use HasFactory;

    protected $table = 'publisher_assets';

    protected $fillable = [
        'publisher_id',
        'publisher_adserver_id',
        'asset_id',
        'url',
        'min_duration_in_hour',
        'price_per_hour',
    ];

    /**
     * The publisher that this asset belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
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
}
