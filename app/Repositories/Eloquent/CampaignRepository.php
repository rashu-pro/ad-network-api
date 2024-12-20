<?php

namespace App\Repositories\Eloquent;

use App\Models\Campaign;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\CampaignRepositoryInterface;

class CampaignRepository extends BaseRepository implements CampaignRepositoryInterface {
    /**
     * CampaignRepository constructor.
     *
     * @param Campaign $model
     */
    public function __construct(Campaign $model) {
        parent::__construct($model);
    }

    /**
     * Publish a draft campaign by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function publishDraft(int $id): bool {
        $campaign = $this->find($id);

        if (!$campaign || $campaign->is_draft === false) {
            return false;
        }

        $campaign->is_draft = false;
        return $campaign->save();
    }
}

