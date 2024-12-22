<?php

namespace App\Services;

use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use Illuminate\Support\Collection;

class AdminService
{
    protected $campaignRepository;
    protected $assetRepository;

    public function __construct(
        CampaignRepositoryInterface $campaignRepository,
        AssetRepositoryInterface $assetRepository,
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->assetRepository = $assetRepository;
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
