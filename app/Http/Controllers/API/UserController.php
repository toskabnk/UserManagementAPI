<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Rules\NoSamePassword;
use App\Utils\CheckPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;


class UserController extends ResponseController
{
    public function create(Request $request)
    {
        //Reglas de validacion
        $rules = [
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'birth_date' => 'required|date_format:Y-m-d|before:today|after:1900-01-01',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'organizations' => '',
            'roles' => ''
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Si la validacion falla, respondemos con los errores
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        //Guardamos los datos validados
        $userData = $validation->validated();

        //Comprobamos si existen las organizaciones y los roles
        if(isset($userData['organizations'])){
            $errors = [];
            foreach($userData['organizations'] as $orgId){
                $org = Organization::find($orgId);
                if(!$org){
                    array_push($errors, 'Orgnization with id: '. $orgId . ' not found');
                }
            }

            if(!empty($errors)){
                return $this->respondNotFound("Organization not found", $errors);
            }
        }

        if(isset($userData['roles'])){
            $errors = [];
            foreach($userData['roles'] as $roleId){
                $role = Role::find($roleId);
                if(!$role){
                    array_push($errors, 'Role with id '. $roleId . ' not found');
                }
            }

            if(!empty($errors)){
                return $this->respondNotFound("Roles not found", $errors);
            }
        }

        //Guardamos la contraseña hasheada
        $userData['password'] = Hash::make($request->password);

        
        //Creamos el usuario
        $copyUserData = $userData;
        unset($copyUserData['organizations']);
        unset($copyUserData['roles']);
        $user = User::create($copyUserData);

        if(isset($userData['organizations'])){
            $user->organizations()->attach($userData['organizations']);
        }

        if(isset($userData['roles'])){
            $user->roles()->attach($userData['roles']);
        }

        //Creamos la estructura de la respuesta
        $response = [
            'message' => 'User created.'
        ];

        //Enviamos la respuesta con los datos
        return $this->respondSuccess($response, 201);
    }

    public function updateUser(Request $request, $id)
    {
        //Conseguimos el usuario de la BD
        $user = User::find($id);

        //Comprobamos si existe
        if (!$user) {
            return $this->respondNotFound('User not found');
        }

        //Reglas de validacion
        $rules = [
            'name' => 'sometimes|required|max:255',
            'surname' => 'sometimes|required|max:255',
            'birth_date' => 'sometimes|required|date_format:Y-m-d|before:today|after:1900-01-01',
            'email' => 'sometimes|required|email',
            'password' => 'required',
            //Comprueba que el email sea unico, pero que ignore el del usuario
            Rule::unique('users')->ignore($user->id)

        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules,config('custom_validation_messages'));

        //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $data = $request->all();

        //Comprueba que la contrasea sea la del usuario para actualizar los datos
        if(!Hash::check($data['password'], $user->password)){
            return $this->respondUnauthorized('Invalid credentials.');
        }
        
        //Eliminamos el parametro password para no actualizar la contraseña
        unset($data['password']);
        unset($data['password_confirmation']);
        
        //Actualizamos el usuario
        $user->update($data);

        $response = [
            'message' => 'User modified.',
            'user' => $user
        ];

        return $this->respondSuccess($response);
    }

    public function viewAll(){
        $user = User::with(['roles','organizations'])->get();

        return $this->respondSuccess(['user' => $user]);
    }

    public function view($id)
    {
        //Conseguimos el usuario de la BD
        $user = User::where('id',$id)->first();

        //Comprobamos si existe
        if (!$user) {
            return $this->respondNotFound('User not found.');
        }

        return $this->respondSuccess(['user' => $user]);
    }

    public function deleteUser($id)
    {
        //Conseguimos el usuario de la BD
        $user = User::find($id);

        //Comprobamos si existe
        if (!$user) {
            return $this->respondNotFound('User not found.');
        }

        //Borramos el usuario de la BD
        User::where('id', $id)->delete();

        return response()->noContent();
    }

    public function changePassword(Request $request, $id)
    {
        //Conseguimos el usuario de la BD
        $user=User::find($id);
        $data = $request->all();

        //Comprobamos si existe
        if (!$user) {
            return $this->respondNotFound('User not found.');
        }

        //Reglas de validacion
        $rules = [
            'oldPassword' => 'required',
            'newPassword' => 'required|confirmed|',
            'newPassword' => new NoSamePassword
        ];

        //Comprueba que la contrasea sea la del usuario para actualizar los datos
        if(!Hash::check($data['oldPassword'], $user->password)){
            return $this->respondUnauthorized('Invalid credentials.');
        }

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        //Actualizamos la contraseña
        $user->update([
            'password' => Hash::make($request->newPassword)
        ]);

        return $this->respondSuccess(['message' => 'Password changed successfully.']);
    }

    public function addRole($userID, $roleID)
    {
        //Conseguimos el usuario actual y sus roles
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        //Consguimos al usuario por ID
        $user = User::find($userID);
        if(!$user){
            return $this->respondNotFound('User not found.');
        }

        //Conseguimos el rol pod ID
        $role = Role::find($roleID);
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        //Buscamos si el rol que intenta añadir ya lo tiene
        foreach($user->roles as $userRoles){
            if($userRoles->name === $role->name){
                return $this->respondForbidden('This user have this role.');
            }
        }
        
        //Añadimos el rol a el usuario
        $user->roles()->attach($roleID);

        return $this->respondSuccess(['message' => 'Role added to the user.']);
    }

    public function removeRole($userID, $roleID)
    {
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $user = User::find($userID);
        if(!$user){
            return $this->respondNotFound('User not found.');
        }

        $role = Role::find($roleID);
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        $findRole = false;
        foreach($user->roles as $userRoles){
            if($userRoles->name === $role->name){
                $findRole = true;
                break;
            }
        }

        if(!$findRole){
            return $this->respondNotFound('The user don\'t have this role.');
        }

        $user->roles()->detach($roleID);

        return $this->respondSuccess(['message' => 'Role removed from the user.']);
    }

    public function addOrganization($userID, $organizationID)
    {
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $user = User::find($userID);
        if(!$user){
            return $this->respondNotFound('User not found.');
        }
        $org = Organization::find($organizationID);
        if(!$org){
            return $this->respondNotFound('Organization not found.');
        }

        foreach($user->organizations as $userOrgs){
            if($userOrgs->name === $org->name){
                return $this->respondForbidden('This user already have this organization.');
            }
        }

        $user->organizations()->attach($organizationID);

        return $this->respondSuccess(['message' => 'Organization added to the user.']);

    }

    public function removeOrganization($userID, $organizationID)
    {
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        //Comprobamos si tiene los permisos necesarios
        if(!CheckPermission::checkAdminPermision($roles)){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        $user = User::find($userID);
        if(!$user){
            return $this->respondNotFound('User not found.');
        }

        $org = Organization::find($organizationID);
        if(!$org){
            return $this->respondNotFound('Organization not found.');
        }

        $findOrg = false;
        foreach($user->organizations as $userOrg){
            if($userOrg->name === $org->name){
                $findRole = true;
                break;
            }
        }

        if(!$findOrg){
            return $this->respondNotFound('User don\'t have this organization.');
        }

        $user->organizations()->detach($organizationID);
        
        return $this->respondSuccess(['message' => 'Organization removed from the user']);
    }
}
