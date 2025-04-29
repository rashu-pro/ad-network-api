<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case PUBLISH = 'publish';
    case COMPLETED = 'completed';

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }
}
