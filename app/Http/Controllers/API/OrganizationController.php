<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Utils\CheckPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends ResponseController
{
    public function create(Request $request)
    {
        //Reglas de validacion
        $rules = [
            'name' => 'required|unique:organizations|min:3',
            'description' => 'sometimes|required'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

         //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $orgData = $validation->validated();

        /** @var \App\Models\User */
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        return $this->respondSuccess(['message' => 'Organization created.'], 201);
    }

    public function view($id)
    {
        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return $this->respondNotFound('Organization not found.');
        }

        return $this->respondSuccess(['organization' => $org]);
    }

    public function viewAll(Request $request){
        //Consigue todos los roles de la BD
        $org = Organization::all();

        return $this->respondSuccess(['organization' => $org]);
    }

    public function update(Request $request, $id)
    {
        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return $this->respondNotFound('Organization not found.');
        }

        //Reglas de validacion
        $rules = [
            'name' => 'sometimes|required|unique:organizations|min:3',
            'description' => 'sometimes|required'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $orgData = $validation->validated();

        //Conseguimos el usuario actual y sus roles
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $org->update($orgData);

        $response = [
            'message' => 'Organization modified.',
            'organization' => $org
        ];

        return $this->respondSuccess($response);
    }

    public function remove($id)
    {
        //Conseguimos el usuario actual y sus roles
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return $this->respondNotFound('Organization not found.');
        }


        $org->delete();

        return $this->respondSuccess(['message' => 'Role deleted successfully.']);
    }

    public function getUserFromOrg($id){
        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return $this->respondNotFound('Organization not found.');
        }

        $users = $org->users;
        unset($org['users']);

        $response = [
            'organization' => $org,
            'users' => $users
        ];

        return $this->respondSuccess($response);
    }

    public function getOrgFromUser($id){
        $user = User::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$user){
            return $this->respondNotFound('User not found.');
        }

        $orgs = $user->organizations;
        unset($user['organizations']);

        $response = [
            'user' => $user,
            'organizations' => $orgs
        ];

        return $this->respondSuccess($response);
    }
}
