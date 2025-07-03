<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignPayment;

class PaymentService
{
    protected $billingService;

    public function __construct()
    {
        $this->billingService = new BillingService();
    }

    public function processPayment(Campaign $campaign, float $amount, string $method, ?string $reference = null)
    {
        CampaignPayment::create([
            'advertiser_id' => $campaign->advertiser_id,
            'campaign_id' => $campaign->id,
            'amount' => $amount,
            'payment_date' => now(),
            'payment_method' => $method,
            'reference' => $reference,
        ]);

        $paid = $campaign->payments()->sum('amount');
        $due = $this->billingService->calculateTotalCampaignBill($campaign);

        $campaign->billing_status = match (true) {
            $paid >= $due => 'PAID',
            $paid > 0 => 'PARTIALLY_PAID',
            default => 'UNPAID',
        };

        $campaign->save();
    }
}

