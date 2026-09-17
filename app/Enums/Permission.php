<?php

namespace App\Enums;

enum Permission: string
{
    case AccessAdmin = 'admin.access';
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
