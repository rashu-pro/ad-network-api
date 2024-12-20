<?php

namespace App\Data;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;

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
            'adserver_id' => 'required|integer',
            'campaign_name' => 'required|string|max:255',
            'target_url' => 'nullable|string|max:255',
            'payment_status' => 'required|in:' . implode(',', PaymentStatus::values()),
            'note' => 'nullable|string',
            'is_draft' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->adserver_id = $data['adserver_id'];
        $this->campaign_name = $data['campaign_name'];
        $this->target_url = $data['target_url'] ?? null;
        $this->payment_status = PaymentStatus::from($data['payment_status']);
        $this->status = CampaignStatus::DRAFT;
        $this->note = $data['note'] ?? null;
        $this->is_draft = $data['is_draft'];
    }
}