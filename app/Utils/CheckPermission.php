<?php

namespace App\Utils;

class CheckPermission
{
    public static function checkAdminPermision($roles)
    {
        foreach ($roles as $role) {
            if ($role->name === 'Admin') {
                return true;
            }
        }

        return false;
    }
}