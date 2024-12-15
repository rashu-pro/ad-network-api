<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DigitalAssetsController extends Controller
{
    public static function middleware() : array
    {
        return [
          'auth:admin'
        ];
    }

    public function index()
    {

    }
}
