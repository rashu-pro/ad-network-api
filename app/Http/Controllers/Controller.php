<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;


#[
    OA\Info(version: "1.0.0", description: "petshop api", title: "Petshop-api Documentation"),
    OA\Server(url: 'http://localhost:8000', description: "local server"),
    OA\Server(url: 'https://adnetwork.masjidapps.net', description: "staging server"),
    OA\Server(url: 'https://adnetwork.masjidapps.net', description: "production server"),
    OA\SecurityScheme( securityScheme: 'bearerAuth', type: "http", name: "Authorization", in: "header", scheme: "bearer"),
]
abstract class Controller
{
    //
}
