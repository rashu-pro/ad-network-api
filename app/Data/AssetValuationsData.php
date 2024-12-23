<?php

namespace App\Data;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AssetValuationsData
{
    public int $asset_id;
    public int $has_url;
    public int $min_population;
    public int $max_population;
    public int $min_duration_in_hour;
    public float $max_price_per_hour;


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
            'asset_id' => 'required|integer',
            'has_url' => 'required|integer',
            'min_population' => 'required|integer',
            'max_population' => 'integer',
            'min_duration_in_hour' => 'integer',
            'max_price_per_hour' => 'decimal:2',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->asset_id = $data['asset_id'];
        $this->has_url = $data['has_url'];
        $this->min_population = $data['min_population'];
        $this->max_population = $data['max_population'];
        $this->min_duration_in_hour = $data['min_duration_in_hour'];
        $this->max_price_per_hour = $data['max_price_per_hour'];
    }
}
