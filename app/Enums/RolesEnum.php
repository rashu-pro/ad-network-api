<?php
namespace App\Enums;

enum RolesEnum: string
{
    case ADVERTISER = 'advertiser';
    case PUBLISHER = 'ad_publisher';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            static::ADVERTISER => 'Advertiser',
            static::PUBLISHER => 'AdPublisher',
            static::ADMIN => 'Admin',
        };
    }
}
