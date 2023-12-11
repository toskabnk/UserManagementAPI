<?php
namespace App\Utils;

use App\Exceptions\Forbidden;
use App\Models\Client;
use App\Models\Organization;

class CheckOrganization
{

    //Metodo para verificar que el Client tiene la Organization
    public static function check(Client $client, Organization $organization, int $memberId): bool
    {
        // Verificar que el cliente tenga la organización
        if (!$client->organizations->contains($organization)) {
            return false;
        }

        // Verificar que el miembro esté asignado a la organización
        return $organization->members->contains($memberId);
    }

    //Metodo para verificar el Member pertenece a alguna organizacion del Client
    public static function checkOrganization(int $memberID, Client $client){
        $organizations = $client->organizations;
        foreach ($organizations as $organization){
            if($organization->members->contains($memberID)){
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the client has the organizations that is trying to add to the member
     *
     * @param array $clientOrganizations
     * @param array $organizations
     *
     * @throws Forbidden
     */
    public static function checkClientOrganizations($clientOrganizations, $memberOrganizations)
    {
        $missingOrganizations = array_diff($memberOrganizations, $clientOrganizations);
        if(count($missingOrganizations) > 0){
            throw new Forbidden('Some organization are not associated with the client.');
        }
    }

    /**
     * Check if the member is associated with the client
     *
     * @param int $memberID
     * @param Client $client
     *
     * @throws Forbidden
     */
    public static function checkMemberOrganization(int $memberID, Client $client){
        $organizations = $client->organizations;
        foreach ($organizations as $organization){
            if($organization->members->contains($memberID)){
                return;
            }
        }
        throw new Forbidden('The member is not associated with the client.');
    }

    /**
     * Check if some members are not associated with the client
     *
     * @param array $memberID
     * @param Client $client
     *
     * @throws Forbidden
     */
    public static function checkMembersOrganization(array $memberID, Client $client){
        foreach ($memberID as $member){
            if(!self::checkOrganization($member, $client)){
                throw new Forbidden('Some member is not associated with the client.');
            }
        }
    }

}
