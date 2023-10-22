<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Utils\CheckPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class RoleController extends Controller
{
    public function create(Request $request)
    {
        //Reglas de validacion
        $rules = [
            'name' => 'required|unique:roles|min:3'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

         //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $roleData = $validation->validated();

        //Conseguimos el usuario actual y sus roles
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $role = Role::create($roleData);

        return $this->respondSuccess(['message' => 'Role created.'], 201);
    }

    public function view($id){
        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        return $this->respondSuccess(['role' => $role]);
    }

    public function viewAll(Request $request){
        //Consigue todos los roles de la BD
        $role = Role::all();

        return $this->respondSuccess(['role' => $role]);
    }

    public function update(Request $request, $id)
    {
        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        $rules = [
            'name' => 'required|unique:roles|min:3'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        //Conseguimos el usuario actual y sus roles
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $role->update([
            'name' => $request->name
        ]);

        return $this->respondSuccess(['message' => 'Role edited.']);
    }

    public function remove($id)
    {
        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        //Conseguimos el usuario actual y sus roles
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $role->delete();

        return $this->respondSuccess(['message' => 'Role deleted.']);
    }

    public function getUsersFromRole($id){
        //Conseguimos el usuario actual y sus roles
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        $users = $role->users;

        $reponse = [
            'role' => $role,
            'users' => $users
        ];

        return $this->respondSuccess($reponse);
    }
}
