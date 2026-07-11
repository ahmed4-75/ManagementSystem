<?php

namespace App\Enums;

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
