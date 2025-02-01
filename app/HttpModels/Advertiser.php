<?php

namespace App\HttpModels;

use App\Enums\RolesEnum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Advertiser extends SecureApiUser
{

    public function __construct(array $data, bool $requireCompanyKey = false)
    {
        $data['businessCategory'] = RolesEnum::ADVERTISER;
        $data['isAdvertiser'] = true;
        parent::__construct($data,$requireCompanyKey);
    }
}
