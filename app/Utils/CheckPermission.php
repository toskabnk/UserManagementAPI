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

    public static function checkSuperAdminPermision($roles)
    {
        foreach ($roles as $role) {
            if ($role->name === 'SuperAdmin') {
                return true;
            }
        }

        return false;
    }

    public static function checkOwnerPermision($user, $orgID, $ownerRolID)
    {
        $hasOwnerRole = $user->roles()
        ->where('roles.id', $ownerRolID)
        ->wherePivot('organization_id', $orgID)
        ->exists();

        return $hasOwnerRole;
    }
}
