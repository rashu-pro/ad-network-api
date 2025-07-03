<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublisherAsset extends Model
{
    use HasFactory, SoftDeletes;

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
}
