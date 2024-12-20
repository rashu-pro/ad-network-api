<?php

namespace Tests\Unit;

use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use App\Services\AdvertiserService;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use App\Models\Advertiser;
use App\Models\Campaign;
use App\Models\Transaction;
use App\Data\CampaignData;
use App\Data\CampaignMappingData;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Collection;
use Mockery;

class AdvertiserServiceTest extends TestCase
{
    protected $advertiserRepository;
    protected $campaignRepository;
    protected $campaignMappingRepository;
    protected $transactionRepository;
    protected $advertiserService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaignRepository = Mockery::mock(CampaignRepositoryInterface::class);
        $this->campaignMappingRepository = Mockery::mock(CampaignMappingRepositoryInterface::class);

        $this->advertiserService = new AdvertiserService(
            $this->campaignRepository,
            $this->campaignMappingRepository
        );
    }

    /**
     * Test that registerAdvertiserInAdServer() returns the ad server advertiser ID
     * and updates the advertiser's adserver_id property.
     *
     * @return void
     */
    public function testRegisterAdvertiserInAdServer()
    {
        $advertiser = new Advertiser(['name' => 'Test Advertiser']);
        $adServerAdvertiserId = 12345;

        $result = $this->advertiserService->registerAdvertiserInAdServer($advertiser);

        $this->assertEquals($adServerAdvertiserId, $result);
        $this->assertEquals($adServerAdvertiserId, $advertiser->adserver_id);
    }

    /**
     * Test that createCampaign() creates a new campaign with the given data.
     *
     * This test verifies that the createCampaign() method correctly interacts
     * with the campaign repository to create a campaign instance and that the
     * returned campaign has the expected properties, including the campaign name
     * and draft status.
     *
     * @return void
     */

    public function testCreateCampaign()
    {
        $campaignData = [
            'adserver_id' => 1,
            'campaign_name' => 'Test Campaign',
            'target_url' => 'http://example.com',
            'payment_status' => PaymentStatus::PENDING->value,
            'note' => 'Test Note',
            'is_draft' => true,
        ];
        $campaign = new Campaign($campaignData);

        $this->campaignRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($campaign);

        $result = $this->advertiserService->createCampaign($campaignData);

        $this->assertInstanceOf(Campaign::class, $result);
        $this->assertEquals($campaignData['campaign_name'], $result->campaign_name);
        $this->assertEquals(CampaignStatus::DRAFT, $result->status);
    }

    /**
     * Test that selectPublishers() correctly creates campaign mappings.
     *
     * This test verifies that the selectPublishers() method correctly
     * interacts with the campaign mapping repository to create a new
     * campaign mapping with the provided publisher data.
     *
     * @return void
     * @throws ValidationException
     */
    public function testSelectPublishers()
    {
        $campaignId = 1;
        $publisherData = [
            [
                'publisher_id' => 1,
                'publisher_asset_id' => 1,
                'start_date' => '2023-01-01',
                'end_date' => '2023-01-31',
                'calculated_price' => 100.0,
                'is_active' => true,
            ]
        ];

        $this->campaignMappingRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::type('array'));

        $this->advertiserService->selectPublishers($campaignId, $publisherData);
    }

    /**
     * Test that updateCampaignStatus() updates the campaign status.
     *
     * This test verifies that the updateCampaignStatus() method correctly
     * interacts with the campaign repository to update the campaign status
     * and that the method returns true on success.
     *
     * @return void
     */
    public function testUpdateCampaignStatus()
    {
        $campaignId = 1;
        $status = CampaignStatus::PENDING;
        $campaign = new Campaign(['id' => $campaignId, 'status' => CampaignStatus::DRAFT]);

        $this->campaignRepository
            ->shouldReceive('find')
            ->once()
            ->with($campaignId)
            ->andReturn($campaign);

        $result = $this->advertiserService->updateCampaignStatus($campaignId, $status->value);

        $this->assertTrue($result);
        $this->assertEquals($status->value, $campaign->status);
    }

    public function testRecordCampaignTransaction()
    {
        $campaignId = 1;
        $amount = 100.0;
        $isPaid = true;
        $transactionData = [
            'campaign_id' => $campaignId,
            'amount' => $amount,
            'is_paid' => $isPaid,
        ];
        $transaction = new Transaction($transactionData);

        Transaction::shouldReceive('create')
            ->once()
            ->with($transactionData)
            ->andReturn($transaction);

        $this->campaignRepository
            ->shouldReceive('find')
            ->once()
            ->with($campaignId)
            ->andReturn(new Campaign(['id' => $campaignId, 'payment_status' => PaymentStatus::PENDING]));

        $result = $this->advertiserService->recordCampaignTransaction($campaignId, $amount, $isPaid);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals($amount, $result->amount);
        $this->assertTrue($result->is_paid);
    }

    /**
     * Test that updateCampaignPaymentStatus() updates the payment status of a campaign.
     *
     * This test verifies that the updateCampaignPaymentStatus() method correctly
     * interacts with the campaign repository to update the payment status of a
     * campaign and that the method returns true on success.
     *
     * @return void
     */
    public function testUpdateCampaignPaymentStatus()
    {
        $campaignId = 1;
        $status = PaymentStatus::PAID;
        $campaign = new Campaign(['id' => $campaignId, 'payment_status' => PaymentStatus::PENDING]);

        $this->campaignRepository
            ->shouldReceive('find')
            ->once()
            ->with($campaignId)
            ->andReturn($campaign);

        $result = $this->advertiserService->updateCampaignPaymentStatus($campaignId, $status->value);

        $this->assertTrue($result);
        $this->assertEquals($status->value, $campaign->payment_status);
    }
}
