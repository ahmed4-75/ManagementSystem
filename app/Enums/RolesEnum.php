<?php

namespace App\Enums;

enum RolesEnum : string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case BACKEND = 'backend';
    case FRONTEND = 'frontend';
    case UI = 'ui';

    case VIEW_ROLES = 'view_roles';
    case CREATE_ROLE = 'create_role';
    case UPDATE_ROLE = 'update_role';
    case DELETE_ROLE = 'delete_role';

    public static function values(): array
    {
        return array_column(self::cases(),'value');
    }
}
