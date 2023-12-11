<?php

namespace App\Services;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Utils\CheckOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientService
{
    /**
     * Create a new Client.
     *
     * @param  array $userData
     * @return array
     *
     * @throws \Exception
     */
    public function create($userData)
    {
        //Guardamos la contraseña hasheada
        $userData['password'] = Hash::make($userData['password']);

        //Creamos el usuario
        $user = User::create($userData);
        $userData['user_id'] = $user->id;

        //Creamos el cliente
        $client = Client::create($userData);

        //Creamos la organizacion si se ha especificado
        if(isset($userData['organization_name'])){
            $organization = Organization::create([
                'name' => $userData['organization_name'],
                'description' => $userData['organization_description'],
                'client_id' => $client->id
            ]);

            //Asignamos la organizacion creada a la organizacion por defecto del cliente
            $client->default_organization = $organization->id;
            $client->save();
        }

        if(isset($userData['roles'])){
            $client->roles()->attach($userData['roles']);
        }

        //Creamos la estructura de la respuesta
        $response = [
            'message' => 'Client created.',
        ];

        //Enviamos la respuesta con los datos
        return $response;
    }

    /**
     * Update a Client.
     *
     * @param  array $userData
     * @param  Client $client
     *
     * @return array
     */
    public function update($userData, $client, $superAdmin)
    {
        //Eliminamos el parametro password y roles
        //Comprobamos si roles esta presente
        $roles = null;
        if(isset($userData['roles'])){
            $roles = $userData['roles'];
        }

        unset($userData['password']);
        unset($userData['roles']);

        $client->update($userData);
        $client->user->update($userData);

        //Si es un superAdmin, actualizamos los roles
        if($superAdmin){
            if($roles){
                $client->roles()->sync($roles);
            }
            //Si password_change esta presente, actualizamos la contraseña
            if(isset($userData['password_change'])){
                $client->user->update([
                    'password' => Hash::make($userData['password_change'])
                ]);
            }
        }

        $response = [
            'message' => 'Client modified.',
        ];

        return $response;
    }

    /**
     * View a Client.
     *
     * @param  Request $request
     * @param  int $clientID
     */
    public function view(Request $request, $clientID)
    {
        //Middleware CheckPermission
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $sameClientID = $request->attributes->get('sameClientID');

        //Comprobamos si el cliente existe
        $client = Client::with(['user','roles','organizations'])->find($clientID);
        if(!$client){
            throw new NotFound('Client not found.');
        }

        //Comprobamos si el usuario actual es el Client pasado por parametro, es un SuperAdmin o un Member que pertenece a una Organization del Client
        if(!$superAdmin){
            if(!$sameClientID){
                if((!$currentUser->member || !CheckOrganization::checkOrganization($currentUser->member->id, $client))){
                    throw new Forbidden('You do not have permission to view this Client.');
                }
            }
        }

        return $client;
    }

    /**
     * Delete a Client.
     *
     * @param  int $clientID
     *
     * @return array
     *
     */
    public function delete($clientID)
    {
        //Conseguimos el usuario de la BD
        $client = Client::find($clientID);

        //Comprobamos si existe
        if (!$client) {
            throw new NotFound('Client not found.');
        }

        //Borramos el cliente y el usuario asignado
        $user = $client->user;

        //Eliminamos todas las organizaciones del cliente
        $organizations = $client->organizations;
        foreach($organizations as $organization){
            $organization->delete();
        }
        $client->delete();
        $user->delete();
    }

    /**
     * Add a Role to a Client.
     *
     * @param  int $clientID
     * @param  int $roleID
     *
     * @return void
     * @throws \Exception
     */
    public function addRoleToClient($clientID, $roleID)
    {
        //Comprobamos si existe el Client y el Role
        $client = Client::find($clientID);
        if(!$client){
            throw new NotFound('Client not found.');
        }

        $role = Role::find($roleID);
        if(!$role){
            throw new NotFound('Role not found.');
        }

        //Buscamos si el rol que intenta añadir ya lo tiene
        foreach($client->roles as $userRoles){
            if($userRoles->id === $role->id){
                throw new Forbidden('The Client already has this role.');
            }
        }

        //Añadimos el rol a el usuario
        $client->roles()->attach($roleID);
    }

    /**
     * Remove a Role from a Client.
     *
     * @param  int $clientID
     * @param  int $roleID
     *
     * @return void
     * @throws \Exception
     */
    public function removeRoleFromClient($clientID, $roleID)
    {
        //Comprobamos si existe el Client y el Role
        $client = Client::find($clientID);
        if(!$client){
            throw new NotFound('Client not found.');
        }

        $role = Role::find($roleID);
        if(!$role){
            throw new NotFound('Role not found.');
        }

        $findRole = false;
        foreach($client->roles as $userRoles){
            if($userRoles->id == $role->id){
                $findRole = true;
                break;
            }
        }

        if(!$findRole){
            throw new Forbidden('Client does not have this role.');
        }

        $client->roles()->detach($roleID);
    }

    /**
     * Add a Organization to a Client.
     *
     * @param  int $clientID
     * @param  int $organizationID
     *
     * @return void
     * @throws \Exception
     */
    public function addOrganizationToClient($clientID, $organizationID)
    {
        //Comprobamos si existe el Client y la Organization
        $client = Client::find($clientID);
        if(!$client){
            throw new NotFound('Client not found.');
        }

        $org = Organization::find($organizationID);
        if(!$org){
            throw new NotFound('Organization not found.');
        }

        foreach($client->organizations as $userOrgs){
            if($userOrgs->id == $org->id){
                throw new Forbidden('Client already has this organization.');
            }
        }

        $client->organizations()->attach($organizationID);
    }

    /**
     * Remove a Organization from a Client.
     *
     * @param  int $clientID
     * @param  int $organizationID
     *
     * @return void
     * @throws \Exception
     */
    public function removeOrganizationFromClient($clientID, $organizationID)
    {
        //Comprobamos si existe el Client y la Organization
        $client = User::find($clientID);
        if(!$client){
            throw new NotFound('Client not found.');
        }

        $org = Organization::find($organizationID);
        if(!$org){
            throw new NotFound('Organization not found.');
        }

        $findOrg = false;
        foreach($client->organizations as $userOrg){
            if($userOrg->name === $org->name){
                $findOrg = true;
                break;
            }
        }

        if(!$findOrg){
            throw new Forbidden('Client does not have this organization.');
        }

        $client->organizations()->detach($organizationID);
    }

    /**
     * Modify default role of a Client.
     *
     * @param  int $clientID
     * @param  int $roleID
     *
     * @return void
     * @throws \Exception
     */
    public function modifyDefaultRole($request, $clientID, $roleID)
    {
        //Middleware data
        $superAdmin = $request->attributes->get('superAdmin');
        $sameClientID = $request->attributes->get('sameClientID');

        //Comprobamos si existe el Client y el Role
        $client = Client::find($clientID);
        if(!$client){
            throw new NotFound('Client not found.');
        }

        $role = Role::find($roleID);
        if(!$role){
            throw new NotFound('Role not found.');
        }

        //Comprobamos si el usuario actual es el Client pasado por parametro o un superAdmin
        if($superAdmin){
            $client['default_role_id'] = $roleID;
            $client->save();
        } else {
            if($sameClientID){
                //Comprobamos que no es un rol prohibido
                if($role->id == 1 || $role->id == 2){
                    throw new Forbidden('This role cant be assigned default.');
                }

                //Comprobamos que el rol que intenta añadir lo tiene el usuario actual
                $findRole = false;
                foreach($client->roles as $userRoles){
                    if($userRoles->id == $role->id){
                        $findRole = true;
                        break;
                    }
                }

                if(!$findRole){
                    throw new Forbidden('You do not have permission to modify this Client.');
                }

                $client['default_role'] = $roleID;
                $client->save();
            } else {
                throw new Forbidden('You do not have permission to modify this Client.');
            }
        }
    }

    /**
     * Modify default organization of a Client.
     *
     * @param Request $request
     * @param int $clientID
     * @param int $organizationID
     *
     * @return void
     * @throws \Exception
     */
    public function modifyDefaultOrganization($request, $clientID, $organizationID)
    {
        //Middleware data
        $superAdmin = $request->attributes->get('superAdmin');
        $sameClientID = $request->attributes->get('sameClientID');

        //Comprobamos si existe el Client y el Role
        $client = Client::find($clientID);
        if(!$client){
            throw new NotFound('Client not found.');
        }

        $org = Organization::find($organizationID);
        if(!$org){
            throw new NotFound('Organization not found.');
        }

        //Comprobamos si el usuario actual es el Client pasado por parametro o un superAdmin
        if($superAdmin){
            $client['default_organization_id'] = $organizationID;
            $client->save();
        } else {
            if($sameClientID){
                //Comprobamos que el rol que intenta añadir lo tiene el usuario actual
                $findOrg = false;
                foreach($client->organizations as $userOrgs){
                    if($userOrgs->id == $org->id){
                        $findOrg = true;
                        break;
                    }
                }

                if(!$findOrg){
                    throw new Forbidden('You do not have permission to modify this Client.');
                }

                $client['default_organization'] = $organizationID;
                $client->save();
            } else {
                throw new Forbidden('You do not have permission to modify this Client.');
            }
        }
    }
}
