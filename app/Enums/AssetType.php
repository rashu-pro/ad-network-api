<?php

namespace App\Enums;

enum AssetType: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }
}
