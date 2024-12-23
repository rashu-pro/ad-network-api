<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use \Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasLocation;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Publisher extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasLocation, InteractsWithMedia;

    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The assets that belong to the Publisher
     *
     * @return HasMany
     */
    public function assets() : HasMany
    {
        return $this->hasMany(PublisherAsset::class);
    }

    public function registerMediaCollections(): void
    {

        $this
            ->addMediaCollection('banner')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg','image/jpg','image/png']);
    }
}
