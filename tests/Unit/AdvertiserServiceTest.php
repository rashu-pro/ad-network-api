<?php

namespace Tests\Unit;

use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use App\Services\AdvertiserService;
use App\Repositories\Interfaces\AdvertiserRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Models\Advertiser;
use App\Models\Campaign;
use App\Models\CampaignMapping;
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
        $advertiserData = [
            'first_name' => 'Test Advertiser',
            'last_name' => 'Test Advertiser',
            'business_name' => 'Test Company',
            'email' => 'test@example.com',
            'password' => 'password',
            'advertiser_phone' => '1234567890',
            'advertiser_website' => 'http://example.com',
            'address' => '123 Test St',
            'note' => 'Test Note'
        ];
        $advertiser = new Advertiser($advertiserData);
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
     * @throws ValidationException
     */
    public function testCreateCampaign()
    {
        $campaignData = [
            'advertiser_id' => 1,
            'campaign_name' => 'Test Campaign',
            'target_url' => 'http://example.com',
            'payment_status' => PaymentStatus::PENDING->value,
            'status' => CampaignStatus::DRAFT->value,
            'note' => null,
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
                'is_active' => false,
            ],
            [
                'publisher_id' => 2,
                'publisher_asset_id' => 2,
                'start_date' => '2023-02-01',
                'end_date' => '2023-02-28',
                'calculated_price' => 200.0,
                'is_active' => false,
            ],
            [
                'publisher_id' => 2,
                'publisher_asset_id' => 1,
                'start_date' => '2023-02-01',
                'end_date' => '2023-02-28',
                'calculated_price' => 150.0,
                'is_active' => false,
            ]
        ];

        $this->campaignMappingRepository
            ->shouldReceive('create')
            ->times(3)
            ->with(Mockery::type('array'));

        $this->advertiserService->selectPublishers($campaignId, $publisherData);
    }

    /**
     * Test that updateCampaignStatus() updates the campaign status.
     *
     * This test verifies that the updateCampaignStatus() method correctly
     * interacts with the campaign repository to update the campaign status
     * and returns true on success.
     *
     * @return void
     */
    public function testUpdateCampaignStatus()
    {
        // Arrange: Define the initial and updated campaign data
        $campaignId = 1;
        $initialCampaignData = [
            'id' => $campaignId,
            'advertiser_id' => 1,
            'campaign_name' => 'Test Campaign',
            'target_url' => 'http://example.com',
            'payment_status' => PaymentStatus::PENDING->value,
            'status' => CampaignStatus::DRAFT->value,
            'note' => null,
            'is_draft' => true,
        ];
        $campaign = new Campaign($initialCampaignData);
        $updatedStatus = CampaignStatus::PENDING->value;

        // Mock the find method to return the campaign data
        $this->campaignRepository
            ->shouldReceive('find')
            ->once()
            ->with($campaignId)
            ->andReturn($campaign);


        // Act: Call the updateCampaignStatus method
        $result = $this->advertiserService->updateCampaignStatus($campaignId, $updatedStatus);
        $this->assertEquals($campaign->is_draft,false);
        // Assert: Verify the result is true
        $this->assertTrue($result);
    }


    /**
     * Test that calculateTotalPrice() correctly calculates the total price of a campaign.
     *
     * This test verifies that the calculateTotalPrice() method sums the calculated_price
     * of all campaign mappings associated with a given campaign ID and returns the correct total.
     * It mocks the campaignMappingRepository to return predefined campaign mappings with known
     * calculated prices and asserts that the total price returned by the method matches the expected value.
     *
     * @return void
     */
    public function testCalculateTotalPrice()
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
            ],
            [
                'publisher_id' => 2,
                'publisher_asset_id' => 2,
                'start_date' => '2023-02-01',
                'end_date' => '2023-02-28',
                'calculated_price' => 200.0,
                'is_active' => true,
            ]
        ];

        $campaignMappings = new Collection([
            new CampaignMapping($publisherData[0]),
            new CampaignMapping($publisherData[1])
        ]);

        $this->campaignMappingRepository
            ->shouldReceive('findByCampaignId')
            ->once()
            ->with($campaignId)
            ->andReturn($campaignMappings);

        $totalPrice = $this->advertiserService->calculateTotalPrice($campaignId);
        $this->assertEquals(300.0, $totalPrice);
    }

    /**
     * Test that recordCampaignTransaction() successfully records a transaction
     * for a campaign and updates the campaign's payment status to paid.
     *
     * This test verifies that the recordCampaignTransaction() method creates a
     * transaction with the correct data and sets the payment status of the campaign
     * to paid if the transaction is successful. It uses mocking to simulate the
     * creation of a transaction and retrieval of a campaign, and asserts that the
     * returned transaction is of the correct class, has the expected amount, and
     * is marked as paid.
     *
     * @return void
     */
    public function testRecordCampaignTransactionSuccess()
    {
        $campaignId = 1;
        $amount = 300.0;
        $isPaid = true;
        $transactionData = [
            'campaign_id' => $campaignId,
            'amount' => $amount,
            'is_paid' => $isPaid,
        ];
        $transaction = new Transaction($transactionData);



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
     * Test that recordCampaignTransaction() records a transaction with the correct data
     * and sets the payment status of the campaign to failed if the transaction is not
     * successful. It uses mocking to simulate the creation of a transaction and retrieval
     * of a campaign, and asserts that the returned transaction is of the correct class,
     * has the expected amount, and is marked as failed.
     *
     * @return void
     */
    public function testRecordCampaignTransactionFailed()
    {
        $campaignId = 1;
        $amount = 300.0;
        $isPaid = false;
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
        $this->assertFalse($result->is_paid);
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

    public function testUpdateCampaignStatusException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->advertiserService->updateCampaignStatus(1, 'invalid_status');
    }

    public function testUpdateCampaignPaymentStatusException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->advertiserService->updateCampaignPaymentStatus(1, 'invalid_status');
    }

    public function testRedirectToPaymentGatewayException()
    {
        $campaignId = 1;
        $campaign = new Campaign(['id' => $campaignId, 'status' => 'invalid_status']);

        $this->campaignRepository
            ->shouldReceive('find')
            ->once()
            ->with($campaignId)
            ->andReturn($campaign);

        $this->expectException(\InvalidArgumentException::class);
        $this->advertiserService->redirectToPaymentGateway($campaignId);
    }
}
