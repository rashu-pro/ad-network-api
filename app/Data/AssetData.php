<?php

namespace App\Data;

use App\Enums\AssetType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AssetData
{
    public string $name;
    public AssetType $type;
    public bool $is_active;


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
            'name' => 'required|string|max:255|unique:assets,name',
            'type' => 'required|in:' . implode(',', AssetType::values()),
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->name = $data['name'];
        $this->type = AssetType::from($data['type'] ?? AssetType::ONLINE);
        $this->is_active = $data['is_active'];
    }
}
