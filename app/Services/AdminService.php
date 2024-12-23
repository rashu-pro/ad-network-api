<?php

namespace App\Services;

use App\Data\AssetData;
use App\Data\AssetValuationsData;
use App\Repositories\Interfaces\AssetValuationRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AdminService
{
    protected $campaignRepository;
    protected $assetRepository;
    protected $asstValutionRepository;

    public function __construct(
        CampaignRepositoryInterface $campaignRepository,
        AssetRepositoryInterface $assetRepository,
        AssetValuationRepositoryInterface $assetValuationRepository,
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->assetRepository = $assetRepository;
        $this->asstValutionRepository = $assetValuationRepository;
    }

    /**
     * Retrieves all campaigns from the database.
     *
     * @return Collection
     */
    public function viewAllCampaigns(): Collection
    {
        return $this->campaignRepository->all();
    }

    /**
     * Update the status of a campaign by campaign ID.
     *
     * @param int $campaignId
     * @param string $status
     * @param string|null $note
     * @return bool
     */
    public function updateCampaignStatus(int $campaignId, string $status, ?string $note = null)
    {
        $campaign = $this->campaignRepository->find($campaignId);
        if ($campaign) {
            $campaign->status = $status;
            if ($note) {
                $campaign->note = $note;
            }
            return $campaign->save();
        }
        return false;
    }

    /**
     * Retrieves all assets from the database.
     *
     * @return Collection
     */

    public function viewAllAssets(): Collection
    {
        return $this->assetRepository->all();
    }

    /**
     * @return Collection
     */
    public function viewAllActiveAssets(): Collection
    {
        return $this->assetRepository->getActiveAssets();
    }

    /**
     * @param array $assetData
     * @return Model
     * @throws ValidationException
     */
    public function createAsset(array $assetData): Model
    {
        $assetData = new AssetData($assetData);
        return $this->assetRepository->create((array)$assetData);
    }

    /**
     * @param array $assetData
     * @return Model
     * @throws ValidationException
     */
    public function createAssetValuation(array $data): Model
    {
        $data = new AssetValuationsData($data);
        return $this->asstValutionRepository->create((array)$data);
    }

    public function deleteAsset(int $id)
    {
        return $this->assetRepository->delete($id);
    }

    /**
     * Retrieves all publishers from the database.
     *
     * @return Collection
     */
    public function viewAllPublishers(): Collection
    {

    }

    /**
     * Retrieves all advertisers from the database.
     *
     * @return Collection
     */
    public function viewAllAdvertisers(): Collection
    {

    }
}
