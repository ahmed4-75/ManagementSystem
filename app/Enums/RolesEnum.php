<?php

namespace App\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'RolesEnum',
    description: 'Available user roles in the system',
    type: 'string',
    enum: ['owner', 'admin', 'backend', 'frontend', 'ui'],
    example: 'admin'
)]
enum RolesEnum : string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case BACKEND = 'backend';
    case FRONTEND = 'frontend';
    case UI = 'ui';

    public static function values(): array
    {
        return array_column(self::cases(),'value');
    }
}
