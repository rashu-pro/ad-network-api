<?php

namespace App\Enums;

enum PublisherCampaignStatus: string
{
    case PENDING = 'pending';
    case APPROVE = 'approve';
    case RUNNING = 'running';
    case PAUSED = 'paused';
    case STOPPED = 'stopped';
    case COMPLETED = 'completed';
    case CONDITIONALLY_APPROVE = 'conditionally_approve';
    case CONDITIONALLY_REJECT = 'conditionally_reject';

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }
}
