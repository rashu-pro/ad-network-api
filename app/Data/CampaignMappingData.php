<?php

namespace App\Data;

use AllowDynamicProperties;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[AllowDynamicProperties] class CampaignMappingData
{
    public int $campaign_id;
    public int $publisher_id;
    public int $publisher_asset_id;
    public string $start_date;
    public string $end_date;
    public float $calculated_price;
    public bool $is_active;

    /**
     * CampaignMappingData constructor.
     *
     * Validates and initializes the campaign mapping data fields.
     *
     * @param array $data An associative array containing the campaign mapping data
     *
     * @throws ValidationException If the validation of the provided data fails
     */
    public function __construct(array $data)
    {
        $validator = Validator::make($data, [
            'campaign_id' => 'required|integer',
            'advertiser_id' => 'required|integer',
            'publisher_id' => 'required|integer',
            'publisher_asset_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
//            'publisher_zone_id' => 'required|integer|exists:publisher_assets,zone_id'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->campaign_id = $data['campaign_id'];
        $this->advertiser_id = $data['advertiser_id'];
        $this->publisher_id = $data['publisher_id'];
        $this->publisher_asset_id = $data['publisher_asset_id'];
        $this->start_date = $data['start_date'];
        $this->end_date = $data['end_date'];
        $this->calculated_price = $data['calculated_price'];
        $this->publisher_zone_id = $data['publisher_zone_id'];
        $this->is_active = (bool)$data['is_active'] ?? false;
    }
}
