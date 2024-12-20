<?php

namespace App\Repositories\Interfaces;


use Illuminate\Database\Eloquent\Collection;

interface CampaignMappingRepositoryInterface extends BaseRepositoryInterface {
    /**
     * Approve a campaign mapping by its ID.
     *
     * @param int $mappingId
     * @return bool
     */
    public function approveCampaign(int $mappingId): bool;

    /**
     * Reject a campaign mapping by its ID with a note.
     *
     * @param int $mappingId
     * @param string $note
     * @return bool
     */
    public function rejectCampaign(int $mappingId, string $note): bool;

    /**
     * Stop a campaign for a specific asset by campaign ID and asset ID.
     *
     * @param int $campaignId
     * @param int $assetId
     * @return bool
     */
    public function stopCampaignForAsset(int $campaignId, int $assetId): bool;

    /**
     * Resume a campaign for a specific asset by campaign ID and asset ID.
     *
     * @param int $campaignId
     * @param int $assetId
     * @return bool
     */
    public function resumeCampaignForAsset(int $campaignId, int $assetId): bool;

    /**
     * Find campaign mappings by campaign ID.
     *
     * @param int $campaignId
     * @return Collection
     */
    public function findByCampaignId(int $campaignId): Collection;
}
