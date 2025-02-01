<?php

namespace App\Policies;

use App\Enums\CampaignStatus;
use App\Enums\PublisherCampaignStatus;
use App\Enums\RolesEnum;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CampaignPolicy
{
    public function update(User $user, Campaign $campaign)
    {
        if ($user->hasRole(RolesEnum::ADVERTISER->value) && $user->id == $campaign->advertiser_id) {
            return $campaign->status != CampaignStatus::PUBLISH && $campaign->is_draft;
        }
        return false;
    }

    public function uploadBanner(User $user, Campaign $campaign)
    {
        if ($user->hasRole(RolesEnum::ADVERTISER->value) && $user->id == $campaign->advertiser_id) {
            return $campaign->status != CampaignStatus::PUBLISH && $campaign->is_draft;
        }
        return false;
    }

    public function updateStatus(User $user, Campaign $campaign)
    {
        if ($user->hasRole(RolesEnum::PUBLISHER->value)) {
            $mappings = $campaign->mappings()->where('publisher_id', $user->id)->get();
            return $mappings->every(fn($mapping) => $mapping->publisher_status == PublisherCampaignStatus::APPROVE);
        }
        return false;
    }

    public function getCampaign(User $user, Campaign $campaign)
    {
        return $user->hasRole(RolesEnum::ADVERTISER->value) && $campaign->advertiser_id == $user->id;
    }
}
