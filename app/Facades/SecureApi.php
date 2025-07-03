<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SecureApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'secure_api';
    }
}
