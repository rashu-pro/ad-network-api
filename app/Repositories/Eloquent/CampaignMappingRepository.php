<?php

namespace App\Repositories\Eloquent;

use App\Models\CampaignMapping;
use App\Repositories\BaseRepository;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use \Illuminate\Database\Eloquent\Collection;
class CampaignMappingRepository extends BaseRepository implements CampaignMappingRepositoryInterface {

    /**
     * CampaignMappingRepository constructor.
     *
     * @param CampaignMapping $model
     */
    public function __construct(CampaignMapping $model) {
        parent::__construct($model);
    }

    /**
     * Approve a campaign mapping by its ID.
     *
     * @param int $mappingId
     * @return bool
     */
    public function approveCampaign(int $mappingId): bool {
        $mapping = $this->find($mappingId);

        if (!$mapping) {
            return false;
        }

        $mapping->status = 'approved';
        $mapping->is_active = true;
        return $mapping->save();
    }

    /**
     * Reject a campaign mapping by its ID with a note.
     *
     * @param int $mappingId
     * @param string $note
     * @return bool
     */
    public function rejectCampaign(int $mappingId, string $note): bool {
        $mapping = $this->model->find($mappingId);

        if (!$mapping) {
            return false;
        }

        $mapping->status = 'rejected';
        $mapping->note = $note;
        $mapping->is_active = false;
        return $mapping->save();
    }

    /**
     * Stop a campaign for a specific asset by campaign ID and asset ID.
     *
     * @param int $campaignId
     * @param int $assetId
     * @return bool
     */
    public function stopCampaignForAsset(int $campaignId, int $assetId): bool {
        $mapping = $this->model->where('campaign_id', $campaignId)->where('asset_id', $assetId)->first();

        if (!$mapping) {
            return false;
        }

        $mapping->is_active = false;
        return $mapping->save();
    }

    /**
     * Resume a campaign for a specific asset by campaign ID and asset ID.
     *
     * @param int $campaignId
     * @param int $assetId
     * @return bool
     */
    public function resumeCampaignForAsset(int $campaignId, int $assetId): bool {
        $mapping = $this->model->where('campaign_id', $campaignId)->where('asset_id', $assetId)->first();

        if (!$mapping) {
            return false;
        }

        $mapping->is_active = true;
        return $mapping->save();
    }

    /**
     * Find campaign mappings by campaign ID.
     *
     * @param int $campaignId
     * @return Collection
     */
    public function findByCampaignId(int $campaignId): Collection
    {
        return $this->model->where('campaign_id', $campaignId)->get();
    }
}

