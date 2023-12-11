<?php

namespace App\Services;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Models\Member;
use App\Models\User;
use App\Models\Organization;
use App\Utils\CheckForbidenRoles;
use App\Utils\CheckOrganization;
use App\Utils\RevokeToken;
use Illuminate\Http\Request;

class MemberService
{
    /**
     * Create a new Member
     *
     * @param array $data
     * @param User $currentUser
     * @param boolean $superAdmin
     * @param boolean $admin
     *
     * @return void
     *
     * @throws \Exception
     */
    public function createMember($userData, $currentUser, $superAdmin, $admin)
    {
        //Creamos el usuario
        $copyUserData = $userData;
        unset($copyUserData['organizations']);
        unset($copyUserData['roles']);
        $user = User::create($copyUserData);
        $copyUserData['user_id'] = $user->id;
        $member = Member::create($copyUserData);

        //Comprobamos si el Client tiene el Role de SuperAdmin o Admin
        if($superAdmin){
            if(isset($userData['organizations'])){
                $member->organizations()->attach($userData['organizations']);
            }

            if(isset($userData['roles'])){
                $member->roles()->attach($userData['roles']);
            }
        } else {
            //Comprobamos si tiene el rol Admin
            if($admin){
                //Comprobamos si va a añadir organizaciones
                if(isset($userData['organizations'])){
                    //Organizaciones del cliente
                    $clientOrgs = $currentUser->client->organizations->pluck('id')->toArray();

                    //Comprobamos si las organizaciones estan asociadas al cliente
                    CheckOrganization::checkClientOrganizations($clientOrgs, $userData['organizations']);

                    $member->organizations()->attach($userData['organizations']);
                //Si no va a añadir organizaciones, le asignamos la que tenga por defecto el Client
                } else {
                    //Si no tiene una organizacion por defecto, Public por defecto
                    $clientDefaultOrg = Organization::find($user->client->default_organization);
                    if(!$clientDefaultOrg){
                        $clientDefaultOrg = Organization::where('name', 'Public')->get();
                    }
                    $member->organizations()->attach($clientDefaultOrg);
                }
                //Comprobamos si va a añadir roles
                if(isset($userData['roles'])){
                    //Roles del cliente
                    $clientRoles = $currentUser->client->roles->pluck('id')->toArray();

                    //Comprobamos si hay roles que no estan permitidos
                    CheckForbidenRoles::checkForbidenRoles($userData['roles']);

                    //Comprobamos si el Client contiene todos los roles que se van a añadir
                    CheckForbidenRoles::checkClientRoles($clientRoles, $userData['roles']);

                    $member->roles()->attach($userData['roles']);
                }

            } else {
                throw new Forbidden('You dont have permission');
            }
        }
    }

    /**
     * Update a Member
     *
     * @param array $userData
     * @param User $currentUser
     * @param boolean $superAdmin
     * @param boolean $admin
     * @param Member $member
     *
     * @return void
     * @throws \Exception
     */
    public function updateMember($userData, $currentUser, $superAdmin, $admin, $member)
    {
        $copyUserData = $userData;
        unset($copyUserData['organizations']);
        unset($copyUserData['roles']);
        $member->update($copyUserData);
        $member->user->update($copyUserData);

        //Comprobamos si el Client tiene el Role de SuperAdmin
        if($superAdmin){
            if(isset($userData['organizations'])){
                $member->organizations()->sync($userData['organizations']);
            }

            if(isset($userData['roles'])){
                $member->roles()->sync($userData['roles']);
            }
        } else {
            //Comprobamos si el Client tiene el Role de Admin y que el Member pertenezca a alguna de las organizaciones del Client
            if($admin){
                //Comprobamos si el Member pertenece a alguna de las organizaciones del Client
                CheckOrganization::checkMemberOrganization($member->id, $currentUser->client);

                //Si quiere actualizar organizaciones
                if(isset($userData['organizations'])){
                    //Organizaciones del cliente
                    $clientOrgs = $currentUser->client->organizations->pluck('id')->toArray();

                    //Comprobamos si las organizaciones estan asociadas al cliente
                    CheckOrganization::checkClientOrganizations($clientOrgs, $userData['organizations']);

                    //Filtra las organizaciones del cliente
                    $memberOrg = $member->organizations->filter(function ($organization) use ($currentUser) {
                        return $currentUser->client->organizations->contains($organization);
                    });

                    //Indexamos las organizaciones
                    $memberOrg = array_values($memberOrg->toArray());
                    //Dejamos solo los ids
                    $memberOrg = array_column($memberOrg, 'id');

                    //Elimina los elementos del array $memberOrg que sea igual a $userData['organizations']
                    $arrayDiff1 = array_diff($memberOrg, $userData['organizations']);
                    $arrayDiff2 = array_diff($userData['organizations'], $memberOrg);
                    $arrayDiff = array_merge($arrayDiff1, $arrayDiff2);

                    //Iteramos sobre las organizaciones que se van a añadir o eliminar
                    foreach ($arrayDiff as $organization) {
                        //Comprobamos si el Member ya tiene la organizacion
                        if(!$member->organizations->contains($organization)){
                            $member->organizations()->attach($organization);
                        } else {
                            $member->organizations()->detach($organization);
                        }
                    }
                }

                //Si quiere actualizar roles
                if(isset($userData['roles'])){
                    //Roles del cliente
                    $clientRoles = $currentUser->client->roles->pluck('id')->toArray();

                    //Borramos rol con id 1 y 2 (SuperAdmin y Admin)
                    $clientRoles = array_diff($clientRoles, [1,2]);
                    $clientRoles = array_values($clientRoles);


                    //Comprobamos si hay roles que no estan permitidos
                    CheckForbidenRoles::checkForbidenRoles($userData['roles']);

                    //Filtra los roles del cliente
                    $memberRol = $member->roles->filter(function ($role) use ($currentUser) {
                        return $currentUser->client->roles->contains($role);
                    });

                    //Comprobamos si el Client contiene todos los roles que se van a añadir
                    CheckForbidenRoles::checkClientRoles($clientRoles, $userData['roles']);

                    //Indexamos los roles
                    $memberRol = array_values($memberRol->toArray());
                    //Dejamos solo los ids
                    $memberRol = array_column($memberRol, 'id');


                    //Elimina los elementos del array $memberRole que sea igual a $userData['roles']
                    $arrayDiff1 = array_diff($memberRol, $userData['roles']);
                    $arrayDiff2 = array_diff($userData['roles'], $memberRol);
                    $arrayDiff = array_merge($arrayDiff1, $arrayDiff2);

                    //Iteramos sobre los roles que se van a añadir o eliminar
                    foreach ($arrayDiff as $role) {
                        //Comprobamos si el Member ya tiene el rol
                        if(!$member->roles->contains($role)){
                            $member->roles()->attach($role);
                        } else {
                            $member->roles()->detach($role);
                        }
                    }
                }
            }
        }
    }

    /**
     * Add roles to the Member
     *
     * @param array $userData
     * @param User $currentUser
     * @param boolean $superAdmin
     * @param boolean $admin
     * @param Member $member
     *
     * @return void
     * @throws \Exception
     */
    public function addRolesToMember($userData, $currentUser, $superAdmin, $admin, $member)
    {
        //Comprobamos si el Client tiene el Role de SuperAdmin
        if($superAdmin){
            if(isset($userData['roles'])){
                $member->roles()->sync($userData['roles']);
                RevokeToken::revokeAllTokensForUser($member->user->id);
            }
        } else {
            //Comprobamos si el Client tiene el Role de Admin y que el Member pertenezca a alguna de las organizaciones del Client
            if($admin){
                //Comprobamos si el Member pertenece a alguna de las organizaciones del Client
                CheckOrganization::checkMemberOrganization($member->id, $currentUser->client);

                //Si quiere actualizar roles
                if(isset($userData['roles'])){
                    //Roles del cliente
                    $clientRoles = $currentUser->client->organizations->pluck('organization_id')->toArray();

                    //Comprobamos si hay roles que no estan permitidos
                    CheckForbidenRoles::checkForbidenRoles($userData['roles']);

                    //Comprobamos si el Client contiene todos los roles que se van a añadir
                    CheckForbidenRoles::checkClientRoles($clientRoles, $userData['roles']);

                    $member->roles()->sync($userData['roles']);
                    RevokeToken::revokeAllTokensForUser($member->user->id);
                }
            }
        }
    }

    /**
     * Remove a Role from a Member
     *
     * @param int $roleId
     * @param User $currentUser
     * @param boolean $superAdmin
     * @param boolean $admin
     * @param Member $member
     *
     * @return void
     * @throws \Exception
     *
     */
    public function removeRoleFromMember($roleId, $currentUser, $superAdmin, $admin, $member)
    {

        //Check if the member contains the role
        if(!$member->roles->contains($roleId)){
            throw new Forbidden('The member doesnt have the role');
        }

        //Comprobamos si el Client tiene el Role de SuperAdmin
        if($superAdmin){
            $member->roles()->detach($roleId);
            RevokeToken::revokeAllTokensForUser($member->user->id);
        } else {
            //Comprobamos si el Client tiene el Role de Admin y que el Member pertenezca a alguna de las organizaciones del Client
            if($admin){
                //Comprobamos si el Member pertenece a alguna de las organizaciones del Client
                CheckOrganization::checkMemberOrganization($member->id, $currentUser->client);

                $member->roles()->detach($roleId);
                RevokeToken::revokeAllTokensForUser($member->user->id);
            }
        }
    }

    /**
     * Add organizations to the Member
     *
     * @param array $userData
     * @param User $currentUser
     * @param boolean $superAdmin
     * @param boolean $admin
     * @param Member $member
     *
     * @return void
     * @throws \Exception
     */
    public function addOrganizationToMember($userData, $currentUser, $superAdmin, $admin, $member)
    {
        //Comprobamos si el Client tiene el Role de SuperAdmin
        if($superAdmin){
            if(isset($userData['organizations'])){
                $member->organizations()->sync($userData['organizations']);
            }
        } else {
            //Comprobamos si el Client tiene el Role de Admin
            if($admin){
                //Si quiere actualizar organizaciones
                if(isset($userData['organizations'])){
                    //Organizaciones del cliente
                    $clientOrgs = $currentUser->client->organizations->pluck('id')->toArray();

                    //Comprobamos si las organizaciones estan asociadas al cliente
                    CheckOrganization::checkClientOrganizations($clientOrgs, $userData['organizations']);

                    //Iteramos sobre las organizaciones que se van a añadir o eliminar
                    foreach ($userData['organizations'] as $organization) {
                        //Comprobamos si el Member ya tiene la organizacion
                        if(!$member->organizations->contains($organization)){
                            $member->organizations()->attach($organization);
                        } else {
                            $member->organizations()->detach($organization);
                        }
                    }
                }
            }
        }
    }

    /**
     * Remove a Organization from a Member
     *
     * @param Organization $organization
     * @param User $currentUser
     * @param boolean $superAdmin
     * @param boolean $admin
     * @param Member $member
     *
     * @return void
     * @throws \Exception
     */
    public function removeOrganizationFromMember($organization, $currentUser, $superAdmin, $admin, $member)
    {
        //Check if the member contains the organization
        if(!$member->organizations->contains($organization->id)){
            throw new Forbidden('The member doesnt have the organization');
        }

        //Comprobamos si el Client tiene el Role de SuperAdmin
        if($superAdmin){
            $member->organizations()->detach($organization->id);
        } else {
            //Comprobamos si el Client tiene el Role de Admin y que el Member pertenezca a alguna de las organizaciones del Client
            if($admin){
                if(!CheckOrganization::check($currentUser->client, $organization, $member->id)){
                    throw new Forbidden('You are trying to remove a organization to a member that is not associated with you.');
                }
                //Comprobamos si el Member pertenece a alguna de las organizaciones del Client
                CheckOrganization::checkMemberOrganization($member->id, $currentUser->client);

                $member->organizations()->detach($organization->id);
            }
        }
    }

    /**
     * Remove a Member
     *
     * @param User $currentUser
     * @param boolean $superAdmin
     * @param boolean $admin
     * @param Member $member
     *
     * @return void
     * @throws \Exception
     */
    public function removeMember($currentUser, $superAdmin, $admin, $member)
    {
        if($superAdmin){
            $user = $member->user;
            $member->delete();
            $user->delete();
        } else {
            if($admin){
                //Comprobamos si el Member pertenece a alguna de las organizaciones del Client
                CheckOrganization::checkMemberOrganization($member->id, $currentUser->client);
                $user = $member->user;
                $member->delete();
                $user->delete();
            }
        }
    }

    /**
     * View a Member
     *
     * @param Request $request
     * @param int $id
     *
     * @return Member
     * @throws \Exception
     */
    public function viewMember(Request $request, $id)
    {
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $isClient = $request->attributes->get('isClient');

        //Comprobamos si el Member existe
        $member = Member::with('user','roles','organizations')->find($id);
        if(!$member){
            throw new NotFound('Member not found');
        }

        if($superAdmin){
            return $member;
        } else {
            if($isClient){
                //Comprobamos si el Member pertenece a alguna de las organizaciones del Client
                CheckOrganization::checkMemberOrganization($member->id, $currentUser->client);

                //Filtra las organizaciones del cliente
                $member['org'] = $member->organizations->filter(function ($organization) use ($currentUser) {
                    return $currentUser->client->organizations->contains($organization);
                });
                unset($member['organizations']);
                $member['organizations'] = $member['org'];
                unset($member['org']);

                //Indexamos las organizaciones
                $member['organizations'] = array_values($member['organizations']->toArray());

                //Filtra los roles del cliente
                $member['rol'] = $member->roles->filter(function ($role) use ($currentUser) {
                    return $currentUser->client->roles->contains($role);
                });
                unset($member['roles']);
                $member['roles'] = $member['rol'];
                unset($member['rol']);

                //Indexamos los roles
                $member['roles'] = array_values($member['roles']->toArray());

                return $member;
            } else {
                return $member;
            }
        }
        throw new Forbidden('You dont have the right permissions');
    }

    /**
     * View all Members
     *
     * @param Request $request
     *
     * @return Member[]
     * @throws \Exception
     */
    public function viewAllMembers(Request $request)
    {
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');

        $query = Member::query()->with(['user','roles','organizations']);

        //Comprobamos si hay filtros y los añadimos a la query
        if($request->has('email')){
            $email = $request->input('email');
            $query->whereHas('user', function ($query) use ($email) {
                $query->where('email', 'like', '%' . $email . '%');
            });
        }

        if($request->has('surname')){
            $surname = $request->input('surname');
            $query->where('surname', 'like', '%' . $surname . '%');
        }

        if($request->has('name')){
            $name = $request->input('name');
            $query->where('name', 'like', '%' . $name . '%');
        }

        if($superAdmin){
            //Conseguimos todos los miembro

            //Ejecutamos la query
            $member = $query->get();

            return $member;
        } else {
            $organizations = $currentUser->client->organizations;
            $roles = $currentUser->client->roles;

            //Obtenemos todos los miembros asociados a estas organizaciones a través de la tabla pivot.
            $query->whereHas('organizations', function ($query) use ($organizations) {
                $query->whereIn('organization_id', $organizations->pluck('id'));
            });

            //Ejecutamos la query
            $members = $query->get();

            //Obtén las organizaciones del cliente.
            $clientOrganizations = $organizations;
            $clientRoles = $roles->pluck('id');

            //Filtra los miembros que no pertenecen a las organizaciones del cliente.
            $members = $members->unique('id');
            $result = $members->map(function ($member) use ($clientOrganizations, $clientRoles) {
                $member['org'] = $member->organizations->filter(function ($organization) use ($clientOrganizations) {
                    return $clientOrganizations->contains($organization);
                });
                unset($member['organizations']);
                $member['organizations'] = $member['org'];
                unset($member['org']);

                //Indexamos las organizaciones
                $member['organizations'] = array_values($member['organizations']->toArray());

                $member['rol'] = $member->roles->filter(function ($role) use ($clientRoles) {
                    return $clientRoles->contains($role->id);
                });
                unset($member['roles']);
                $member['roles'] = $member['rol'];
                unset($member['rol']);

                //Indexamos los roles
                $member['roles'] = array_values($member['roles']->toArray());

                return $member;
            });

            $result->load('user');

            return $result;
        }
    }
}
