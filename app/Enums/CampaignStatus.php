<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVE = 'approve';
    case CONDITIONALLY_APPROVE = 'conditionally_approve';
    case CONDITIONALLY_REJECT = 'conditionally_reject';

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }
}
