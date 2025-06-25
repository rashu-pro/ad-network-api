<?php

namespace App\Services;

use App\Data\AssetData;
use App\Data\AssetValuationsData;
use App\Data\ZoneData;
use App\Repositories\Interfaces\AssetCategoryRepositoryInterface;
use App\Repositories\Interfaces\AssetTypeRepositoryInterface;
use App\Repositories\Interfaces\AssetValuationRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use App\Repositories\Interfaces\ZoneRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AdminService
{
    protected $campaignRepository;
    protected $assetRepository;
    protected $asstValutionRepository;
    protected $zoneRepository;
    protected $assetTypeRepository;
    protected $assetCategoryRepository;

    public function __construct(
        CampaignRepositoryInterface $campaignRepository,
        AssetRepositoryInterface $assetRepository,
        AssetValuationRepositoryInterface $assetValuationRepository,
        ZoneRepositoryInterface $zoneRepository,
        AssetTypeRepositoryInterface $assetTypeRepository,
        AssetCategoryRepositoryInterface $assetCategoryRepository
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->assetRepository = $assetRepository;
        $this->asstValutionRepository = $assetValuationRepository;
        $this->zoneRepository = $zoneRepository;
        $this->assetTypeRepository = $assetTypeRepository;
        $this->assetCategoryRepository = $assetCategoryRepository;
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

    /**
     * @return Collection
     */
    public function allZones(): Collection
    {
        return $this->zoneRepository->getActiveZones();
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function viewZone(int $id): Model
    {
        return $this->zoneRepository->find($id);
    }

    /**
     * @param int $assetId
     * @return Collection
     */
    public function allZonesByAssetId(int $assetId): Collection
    {
        return $this->zoneRepository->getZonesByAssetId($assetId);
    }

    /**
     * @param array $zoneData
     * @return Model
     * @throws ValidationException
     */
    public function createZone(array $zoneData): Model
    {
        $zoneData = new ZoneData($zoneData);
        return $this->zoneRepository->create((array)$zoneData);
    }

    /**
     * @param int $id
     * @param $zoneData
     * @return bool
     */
    public function updateZone(int $id, $zoneData): bool
    {
        return $this->zoneRepository->update($id, $zoneData);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteZone(int $id): bool
    {
        return $this->zoneRepository->delete($id);
    }

    /**
     * @return Collection
     */
    public function getAllActiveAssetTypes(): Collection
    {
        return $this->assetTypeRepository->getAllActiveAssetTypes();
    }

    /**
     * @param int $assetTypeId
     * @return Collection
     */
    public function getAllActiveAssetCategoriesByAssetTypeId(int $assetTypeId): Collection
    {
        return $this->assetCategoryRepository->getAllActiveAssetCategoriesByAssetTypeId($assetTypeId);
    }
}
