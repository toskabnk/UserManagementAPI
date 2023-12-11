<?php
namespace App\Utils;

use App\Exceptions\Forbidden;
use App\Models\Client;

class CheckForbidenRoles
{
    public static function hasForbidenRole(Client $client, int $roleId): bool
    {
        $forbidenRoles = [1, 2]; // IDs de los roles prohibidos (Admin y SuperAdmin)

        //Comprueba si el Client es un SuperAdmin para poder aplicar roles prohibidos o roles que no tenga asignados
        if(!$client->roles->contains(2)) {
            if (in_array($roleId, $forbidenRoles)) {
                return true;
            }

            return !$client->roles->contains($roleId);
        }

        return true;
    }

    /**
     * Check if the client are trying to add a forbidden role
     *
     * @param array $roles
     *
     * @throws Forbidden
     */
    public static function checkForbidenRoles($roles)
    {
        $forbidenRoles = [1, 2]; // IDs de los roles prohibidos (Admin y SuperAdmin)

        $missingForbiddenRoles = array_diff($forbidenRoles, $roles);

        if(count($missingForbiddenRoles) < 2){
            throw new Forbidden('You are trying to add a forbidden role');
        }
    }

    /**
     * Check if the client has the roles that is trying to add to the member
     *
     * @param array $clientRoles
     * @param array $roles
     *
     * @throws Forbidden
     */
    public static function checkClientRoles($clientRoles, $memberRoles)
    {
        $missingRoles = array_diff($memberRoles, $clientRoles);
        if(count($missingRoles) > 0){
            throw new Forbidden('Some role are not associated with the client.');
        }
    }
}
