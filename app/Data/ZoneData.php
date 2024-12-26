<?php

namespace App\Data;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ZoneData
{
    public string $zone_name;
    public int $asset_id;
    public float $width;
    public float $height;
    public float $type_id;

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
            'asset_id' => 'required|integer|exists:assets,id',
            'zone_name' => 'required|string|max:255|unique:zones,zone_name',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'type_id' => 'integer|nullable'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->asset_id = $data['asset_id'];
        $this->zone_name = $data['zone_name'];
        $this->width = $data['width'];
        $this->height = $data['height'];
        $this->type_id = $data['type_id'];
    }
}
