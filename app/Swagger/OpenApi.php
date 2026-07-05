<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'ManagementSystem API',
    version: '1.0.0',
    description: 'API documentation for ManagementSystem'
)]
#[OA\Server(
    url: 'http://localhost/ManagementSystem/public',
    description: 'Local server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class OpenApi
{}
