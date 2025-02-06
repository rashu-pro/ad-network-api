<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Enums\RolesEnum;
use App\Models\CampaignMapping;
use App\Models\User;

class CampaignMappingPolicy
{
    public function reuploadBanner(User $user, CampaignMapping $campaign)
    {
        return $user->hasRole(RolesEnum::ADVERTISER->value) && $campaign->advertiser_id == $user->id && $campaign->campaign->payment_status == PaymentStatus::PAID;
    }
}
