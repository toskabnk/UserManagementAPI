<?php

namespace App\Services;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Models\Role;
use App\Utils\CheckForbidenRoles;
use App\Utils\CheckOrganization;
use Illuminate\Http\Request;

class RoleService
{
    /**
     * Create a new Role
     *
     * @param Request $request
     *
     * @return void
     * @throws \Exception
     */
    public function create(Request $request, $roleData)
    {
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');
        $isClient = $request->attributes->get('isClient');

        //Comprobamos si tiene los permisos necesarios
        if(!$isClient && !$superAdmin){
            throw new Forbidden('You don\'t have the right permissions.');
        }

        $role = Role::create($roleData);

        if($superAdmin){
            //Si es un superAdmin, añade los clientes y miembros que se le pasen sin restriccion
            if($request->has('clients')){
                $role->clients()->attach($roleData['clients']);
            }
            if($request->has('members')){
                $role->members()->attach($roleData['members']);
            }
        } else {
            if($admin){
                //Si es un admin, solo puede añadir miembros de sus organizaciones
                if($request->has('members')){
                    //Comprobamos si los Members pertenecen a alguna de las organizaciones del Client
                    CheckOrganization::checkMembersOrganization($roleData['members'], $currentUser->client);
                    $role->members()->attach($request['members']);
                    $role->clients()->attach($currentUser->client->id);
                }
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }
    }

    /**
     * View a Role by id
     *
     * @param Request $request
     * @param int $id
     *
     * @return Role
     * @throws \Exception
     */
    public function view(Request $request, $id)
    {
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Conseguimos el rol de la BD
        $role = null;
        if($superAdmin){
            $role = Role::with('members.user', 'clients')->find($id);
        } else {
            if($admin){
                $role = Role::with('members.user', 'clients')->whereHas('clients', function($query) use ($currentUser){
                    $query->where('client_id', $currentUser->id);
                })->find($id);
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }

        //Si no lo encuentra mandamos un 404
        if(!$role){
            throw new NotFound('Role not found.');
        }

        return $role;
    }

    /**
     * View all Roles from the current Client
     *
     * @param Request $request
     *
     * @return Role[]
     * @throws \Exception
     */
    public function viewAll(Request $request)
    {
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');

        $query = Role::query()->with('members', 'clients');

        if($request->has('name')){
            $name = $request->input('name');
            $query->where('name', 'like', '%' . $name . '%');
        }

        if($request->has('memberName')){
            $memberName = $request->input('memberName');
            $query->whereHas('members', function ($query) use ($memberName) {
                $query->where('name', 'like', '%' . $memberName . '%');
            });
        }

        if($superAdmin){
            return $roles = $query->get();
        } else {
            //Conseguimos todos los roles del cliente actual
            $query->with('members', 'clients')->whereHas('clients', function($query) use ($currentUser){
                $query->where('client_id', $currentUser->id);
            })->get();

            $roles = $query->get();

            //Filtramos los resultados en los roles los miembros que el tenga en alguna organizacion
            $result = $roles->map(function ($role) use ($currentUser) {
                $members = $currentUser->client->organizations->flatMap(function ($organization) use ($role) {
                    return $organization->members->filter(function ($member) use ($role) {
                        return $member->roles->contains($role);
                    });
                });

                //Sustituimos los miembros por los filtrados
                unset($role['members']);
                $role['members'] = $members;
                //Eliminamos los clientes
                unset($role['clients']);

                return $role;
            });

            //Eliminamos el rol de Admin
            foreach ($result as $key => $rol){
                if($rol['name'] == 'Admin'){
                    unset($result[$key]);
                    break;
                }
            }
            $result = $result->toArray();
            //Reindexamos el array
            $result = array_values($result);

            return $result;
        }

        throw new Forbidden('You don\'t have the right permissions.');
    }

    /**
     * Update a Role
     *
     * @param Request $request
     * @param Role $role
     * @param array $roleData
     *
     * @return void
     * @throws \Exception
     */
    public function update(Request $request, $role, $roleData)
    {
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        if($superAdmin){
            $role->update($roleData);
            //Si es un superAdmin, añade los clientes y miembros que se le pasen sin restriccion
            if($request->has('clients')){
                $role->clients()->sync($roleData['clients']);
            }
            if($request->has('members')){
                $role->members()->sync($roleData['members']);
            }
        } else {
            if($admin){
                //Comprobamos si el client contiene el rol a actualizar y si no es un rol prohibido
                CheckForbidenRoles::checkForbidenRoles([$role->id]);
                if($currentUser->client->roles->contains($role)){
                    $role->update($roleData);
                    if($request->has('members')){
                        $role->members()->sync($roleData['members']);
                    }
                } else {
                    throw new Forbidden('You don\'t have the right permissions.');
                }
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }
    }

    /**
     * Delete a Role
     *
     * @param Request $request
     * @param int $id
     *
     * @return void
     * @throws \Exception
     */
    public function remove(Request $request, $id)
    {
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            throw new NotFound('Role not found.');
        }

        if($superAdmin){
            $role->delete();
        } else {
            if($admin){
                //Comprobamos si no es un rol prohibido
                CheckForbidenRoles::checkForbidenRoles([$role->id]);
                //Comprobamos si el client contiene el rol a eliminar
                if($currentUser->client->roles->contains($role)){
                    $role->delete();
                } else {
                    throw new Forbidden('You don\'t have the right permissions.');
                }
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }
    }

    /**
     * Get all Users from a Role
     *
     * @param Request $request
     * @param int $id
     *
     * @return void
     * @throws \Exception
     */
    public function getMembersFromRole(Request $request, $id){
        //Datos del Middleware
        $superAdmin = $request->attributes->get('superAdmin');

        //Conseguimos el rol de la BD
        $role = Role::find($id)->with('members', 'clients');

        //Si no lo encuentra mandamos un 404
        if(!$role){
            throw new NotFound('Role not found.');
        }

        $members = $role->members;
        $clients = $role->clients;

        unset($role['members']);
        unset($role['clients']);

        if($superAdmin){
            $reponse = [
                'role' => $role,
                'members' => $members,
                'clients' => $clients
            ];

            return $reponse;
        } else {
            $reponse = [
                'role' => $role,
                'members' => $members,
            ];

            return $reponse;
        }
    }
}
