<?php

namespace App\HttpModels;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Publisher extends SecureApiUser
{

    public function __construct(array $data, bool $requireCompanyKey = false)
    {
        $data['businessCategory'] = 'AdPublisher';
        parent::__construct($data,$requireCompanyKey);
    }
}
