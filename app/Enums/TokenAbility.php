<?php

namespace App\Enums;

enum TokenAbility: string
{
    case ISSUE_ACCESS_TOKEN = 'issue-access-token';
    case ACCESS_API = 'access-api';
    public static function values(): array {
        return array_column(self::cases(), 'value');
    }
}
