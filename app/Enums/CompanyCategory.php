<?php
namespace App\Enums;

enum CompanyCategory: string
{
    case ADVERTISER = 'Advertiser';
    case PUBLISHER = 'AdPublisher';

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }
}
