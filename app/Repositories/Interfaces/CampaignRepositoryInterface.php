<?php

namespace App\Repositories\Interfaces;

interface CampaignRepositoryInterface extends BaseRepositoryInterface {
    /**
     * Publish a draft campaign by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function publishDraft(int $id): bool;
}
