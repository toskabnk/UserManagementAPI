<?php

namespace App\Services;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Models\Member;
use App\Models\Organization;
use App\Utils\CheckOrganization;
use Illuminate\Http\Request;

class OrganizationService
{

    /**
     * Create a new Organization
     *
     * @param array $orgData
     * @param User $currentUser
     * @param bool $superAdmin
     *
     * @return void
     */
    public function create($orgData, $currentUser, $superAdmin){
        //Si es superAdmin, puede asignar la organizacion a cualquier cliente
        if($superAdmin){
            //Si tiene pasado por parametro el client_id, lo asignamos, si no, se asigna al Client del superAdmin
            if (isset($orgData['client_id'])) {
                $orgData['client_id'] = ($orgData['client_id']);
            } else {
                $orgData['client_id'] = $currentUser->client->id;
            }
        } else {
            //Si no, solo puede asignar la organizacion a su cliente
            $orgData['client_id'] = $currentUser->client->id;
        }
        $organization = Organization::create($orgData);;
    }

    /**
     * View the Organization with the given id
     *
     * @param int $id
     *
     * @return Organization
     */
    public function view($id){
        $org = Organization::with('members', 'client')->find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            throw new NotFound('Organization not found.');
        }

        return $org;
    }

    /**
     * View all the Organizations
     *
     * @return Organization
     */
    public function viewAll(Request $request){
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        $query = Organization::query();

        if($request->has('name')){
            $name = $request->input('name');
            $query->where('name', 'like', '%' . $name . '%');
        }

        if($request->has('description')){
            $description = $request->input('description');
            $query->where('description', 'like', '%' . $description . '%');
        }

        //Si es superAdmin, devuelve todas las organizaciones
        if($superAdmin){
            return $query->get();
        } else {
            //Si no, devuelve las organizaciones del cliente
            $query->where('client_id', $currentUser->client->id);

            return $query->get();

            //return $currentUser->client->organizations;
        }
    }

    /**
     * Update the Organization with the given id
     *
     * @param Request $request
     * @param int $id
     * @param array $orgData
     *
     * @return void
     * @throws \Exception
     */
    public function update(Request $request, $organizationId, $orgData){
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Buscamos la organizacion y si no se encuentra, lanzamos un error
        $org = Organization::find($organizationId);
        if(!$org){
            throw new NotFound('Organization not found.');
        }

        //Si es superAdmin, puede actualizar cualquier organizacion
        if($superAdmin){
            return $org->update($orgData);
        } else {
            //Si no, solo puede actualizar las organizaciones del cliente
            if($admin){

                //Si tiene pasado por parametro el client_id, lo eliminamos
                if (isset($orgData['client_id'])) {
                    unset($orgData['client_id']);
                }

                if(!$currentUser->client->organizations->contains($org)){
                    throw new Forbidden('You don\'t have the right permissions.');
                }

                return $org->update($orgData);
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }
    }

    /**
     * Delete the Organization with the given id
     *
     * @param Request $request
     * @param int $id
     *
     * @return void
     */
    public function remove(Request $request, $organizationId){
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Buscamos la organizacion y si no se encuentra, lanzamos un error
        $org = Organization::find($organizationId);
        if(!$org){
            throw new NotFound('Organization not found.');
        }

        //Si es superAdmin, puede eliminar cualquier organizacion
        if($superAdmin){
            return $org->delete();
        } else {
            //Si no, solo puede eliminar las organizaciones del cliente
            if($admin){
                if(!$currentUser->client->organizations->contains($org)){
                    throw new Forbidden('You don\'t have the right permissions.');
                }
                return $org->delete();
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }
    }

    /**
     * Get the users from the Organization with the given id
     *
     * @param Request $request
     * @param int $organizationId
     *
     * @return array
     * @throws \Exception
     */
    public function getUsers(Request $request, $organizationId){
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Buscamos la organizacion y si no se encuentra, lanzamos un error
        $org = Organization::find($organizationId);
        if(!$org){
            throw new NotFound('Organization not found.');
        }
        //Si es superAdmin, puede ver los usuarios de cualquier organizacion
        if($superAdmin){
            $reponse = [
                'organization' => $org,
                'members' => $org->members
            ];
            return $reponse;

        } else {
            //Si no, solo puede ver los usuarios de las organizaciones del cliente
            if($admin){
                if(!$currentUser->client->organizations->contains($org)){
                    throw new Forbidden('You don\'t have the right permissions.');
                }

                $reponse = [
                    'organization' => $org,
                    'members' => $org->members
                ];

                return $reponse;
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }
    }

    /**
     * Get the organizations from the Member with the given id
     *
     * @param Request $request
     * @param int $memberId
     *
     * @return array
     * @throws \Exception
     */
    public function getOrgsFromUser(Request $request, $memberId){
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Buscamos el Member y si no se encuentra, lanzamos un error
        $member = Member::find($memberId);
        if(!$member){
            throw new NotFound('Member not found.');
        }
        //Si es superAdmin, puede ver los usuarios de cualquier organizacion
        if($superAdmin){
            $reponse = [
                'member' => $member,
                'organizations' => $member->organizations
            ];
            return $reponse;

        } else {
            //Si no, solo puede ver los usuarios de las organizaciones del cliente
            if($admin){
                CheckOrganization::checkMemberOrganization($memberId, $currentUser->client);

                $reponse = [
                    'member' => $member,
                    'organizations' => $member->organizations
                ];
                return $reponse;
            } else {
                throw new Forbidden('You don\'t have the right permissions.');
            }
        }
    }
}
