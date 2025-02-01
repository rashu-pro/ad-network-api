<?php

namespace App\Data;

use AllowDynamicProperties;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;

#[AllowDynamicProperties]
class CampaignData
{
    public int $adserver_id;
    public string $campaign_name;
    public ?string $target_url;
    public PaymentStatus $payment_status;
    public CampaignStatus $status;
    public ?string $note;
    public bool $is_draft;


    /**
     * CampaignData constructor.
     *
     * Validates and initializes the campaign data fields.
     *
     * @param array $data An associative array containing the campaign data
     *
     * @throws ValidationException If the validation of the provided data fails
     */
    public function __construct(array $data)
    {
        $validator = Validator::make($data, [
            'advertiser_id' => 'required|integer|exists:users,id',
            'advertiser_adserver_id' => 'nullable|integer',
            'campaign_name' => 'required|string|max:255',
            'target_url' => 'nullable|string|max:255',
            'payment_status' => 'nullable|in:' . implode(',', PaymentStatus::values())
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->advertiser_id = $data['advertiser_id'];
        $this->advertiser_adserver_id = $data['advertiser_adserver_id'] ?? null;
        $this->campaign_name = $data['campaign_name'];
        $this->target_url = $data['target_url'] ?? null;
        $this->payment_status = PaymentStatus::from($data['payment_status'] ?? PaymentStatus::PENDING->value);
        $this->status = CampaignStatus::DRAFT;
        $this->is_draft = true;
    }
}
