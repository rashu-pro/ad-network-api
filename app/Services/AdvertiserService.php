<?php

namespace App\Services;

use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use App\Models\Advertiser;
use App\Models\Transaction;
use App\Data\CampaignData;
use App\Data\CampaignMappingData;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AdvertiserService
{
    protected $campaignRepository;
    protected $campaignMappingRepository;
    public function __construct(
        CampaignRepositoryInterface $campaignRepository,
        CampaignMappingRepositoryInterface $campaignMappingRepository,
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->campaignMappingRepository = $campaignMappingRepository;
    }

    /**
     * Register an advertiser in the ad server.
     *
     * @param Advertiser $advertiser
     * @return int Ad server advertiser ID
     */
    public function registerAdvertiserInAdServer(Advertiser $advertiser): int
    {
        // Logic to register advertiser in ad server
        // Assuming the ad server returns an advertiser ID
        $adServerAdvertiserId = '12345';
        $advertiser->adserver_id = $adServerAdvertiserId;
        $advertiser->save();

        return $adServerAdvertiserId;
    }

    /**
     * Create a new campaign.
     *
     * @param array $campaignData
     * @return Model
     * @throws ValidationException
     */
    public function createCampaign(array $campaignData): Model
    {
        $campaignData = new CampaignData($campaignData);
        return $this->campaignRepository->create((array)$campaignData);
    }

    /**
     * Select publishers for a given campaign and create corresponding campaign mappings.
     *
     * @param int $campaignId The ID of the campaign for which publishers are being selected.
     * @param array $publisherData An array containing data for each publisher to be associated with the campaign.
     *
     * This method iterates over each publisher's data, assigns the campaign ID, and creates a new campaign mapping
     * using the CampaignMappingData class, which is then stored in the campaign mapping repository.
     * @throws ValidationException
     */
     public function selectPublishers(int $campaignId, array $publisherData): void
     {
         foreach ($publisherData as $data) {
             $data['campaign_id'] = $campaignId;
             $campaignMappingData = new CampaignMappingData($data);
             $this->campaignMappingRepository->create((array) $campaignMappingData);
         }
     }

    /**
     * Create campaign mappings for a campaign.
     *
     * @param int $campaignId
     * @param array $publisherAssetData
     * @return void
     * @throws ValidationException
     */
    public function createCampaignMappings(int $campaignId, array $publisherAssetData): void
    {
        foreach ($publisherAssetData as $data) {
            $data['campaign_id'] = $campaignId;
            $campaignMappingData = new CampaignMappingData($data);
            $this->campaignMappingRepository->create((array) $campaignMappingData);
        }
    }

    /**
     * Calculate the total price for a given campaign.
     *
     * @param int $campaignId The ID of the campaign for which the total price is being calculated.
     * @return float The total price of the campaign.
     */
    public function calculateTotalPrice(int $campaignId): float
    {
        $campaignMappings = $this->campaignMappingRepository->findByCampaignId($campaignId);
        $totalPrice = 0;

        foreach ($campaignMappings as $mapping) {
            $totalPrice += $mapping->calculated_price;
        }

        return $totalPrice;
    }

    /**
     * Update the status of a campaign.
     *
     * @param int $campaignId
     * @param string $status
     * @return bool
     */
    public function updateCampaignStatus(int $campaignId, string $status): bool
    {
        if (!in_array($status, [CampaignStatus::DRAFT->value, CampaignStatus::PENDING->value])) {
            throw new \InvalidArgumentException('Invalid status. Status can only be draft or pending.');
        }

        $campaign = $this->campaignRepository->find($campaignId);
        if ($campaign) {
            $campaign->status = $status;
            if ($campaign->status !== CampaignStatus::DRAFT) {
                $campaign->is_draft = false;
            }
            return $campaign->save();
        }
        return false;
    }

    /**
     * Redirect the user to the payment gateway to make the payment for the campaign.
     *
     * @param int $campaignId The ID of the campaign for which the payment is being made.
     *
     * This method should redirect the user to a URL that will allow them to make a payment for the campaign.
     * The URL and its parameters should be determined by the payment gateway API.
     */
    public function redirectToPaymentGateway(int $campaignId): void
    {
        $campaign = $this->campaignRepository->find($campaignId);

        if (!$campaign || ($campaign->payment_status === PaymentStatus::PAID->value)) {
            throw new \InvalidArgumentException('Campaign is already paid.');
        }

        // Logic to redirect to payment gateway
    }

    /**
     * Record a transaction for a campaign and set the campaign payment status to be paid.
     *
     * @param int $campaignId
     * @param float $amount
     * @param bool $isPaid
     * @return Transaction
     */
    public function recordCampaignTransaction(int $campaignId, float $amount, bool $isPaid): Transaction
    {
        $transactionData = [
            'campaign_id' => $campaignId,
            'amount' => $amount,
            'is_paid' => $isPaid,
        ];

        $transaction = Transaction::create($transactionData);

        if ($isPaid) {
            $this->updateCampaignPaymentStatus($campaignId, PaymentStatus::PAID->value);
        } else {
            $this->updateCampaignPaymentStatus($campaignId, PaymentStatus::FAILED->value);
        }

        return $transaction;
    }

    /**
     * Update the payment status of a campaign.
     *
     * @param int $campaignId
     * @param string $status
     * @return bool
     */
    public function updateCampaignPaymentStatus(int $campaignId, string $status): bool
    {
        if (!in_array($status, [PaymentStatus::PAID->value, PaymentStatus::FAILED->value])) {
            throw new \InvalidArgumentException('Invalid payment status.');
        }

        $campaign = $this->campaignRepository->find($campaignId);
        if ($campaign) {
            $campaign->payment_status = $status;
//            if($campaign->payment_status == PaymentStatus::PAID->value){}
            return $campaign->save();
        }
        return false;
    }

    public function allCampaigns(int $advertiserId) : Collection
    {
        $campaigns = $this->campaignRepository->allAdvertiserCampaigns($advertiserId);
        return $campaigns;
    }
}
