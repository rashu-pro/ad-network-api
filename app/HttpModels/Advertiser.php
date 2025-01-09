<?php

namespace App\HttpModels;

use App\Enums\CompanyCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Advertiser extends SecureApiUser
{

    public function __construct(array $data, bool $requireCompanyKey = false)
    {
        $data['companyCategory'] = CompanyCategory::ADVERTISER;
        parent::__construct($data,$requireCompanyKey);
    }
}
