<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AdServer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'adserver';
    }
}
