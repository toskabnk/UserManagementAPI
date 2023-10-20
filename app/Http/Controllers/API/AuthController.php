<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Rules\NoSamePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        //Reglas de validacion
        $rules = [
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'birth_date' => 'required|date_format:Y-m-d|before:today|after:1900-01-01',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //
        if($validation->fails())
        {
            return response()->json([
                'message' => $validation->errors(),
            ],422);
        }

        $userData = $validation->validated();
        
        $userData['password'] = Hash::make($request->password);

        $user = User::create($userData);

        $accessToken = $user->createToken('authToken')->accessToken;

        return response([
            'message' => $user,
            'access_token' => $accessToken
        ],201);
    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if(!auth()->attempt($loginData)) {
            return response([
                'message' => 'Invalid Credentials'
            ]);
        }
        
        /** @var \App\Models\User */
        $currentUser = Auth::user();
        $accessToken = $currentUser->createToken('authToken')->accessToken;
        return response([
            'user' => $currentUser,
            'accessToken' => $accessToken
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        //Conseguimos el usuario de la BD
        $user = User::find($id);

        //Comprobamos si existe
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
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
            return response()->json(['message' => $validation->errors()],422);
        }

        $data = $request->all();

        //Comprueba que la contrasea sea la del usuario para actualizar los datos
        if(!Hash::check($data['password'], $user->password)){
            return response()->json(['message' => 'Password incorrect'],401);
        }
        
        //Eliminamos el parametro password para no actualizar la contraseña
        unset($data['password']);
        unset($data['password_confirmation']);
        
        //Actualizamos el usuario
        $user->update($data);

        return response([
            'userData' => $user,
            'request' => $request->all()
        ],200);
    }

    public function getUser($id)
    {
        //Conseguimos el usuario de la BD
        $user = User::select('name', 'surname', 'email')->where('id',$id)->first();

        //Comprobamos si existe
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            ]);
    }

    public function deleteUser($id)
    {
        //Conseguimos el usuario de la BD
        $user = User::find($id);

        //Comprobamos si existe
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
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
            return response()->json(['message' => 'User not found'], 404);
        }

        //Reglas de validacion
        $rules = [
            'oldPassword' => 'required',
            'newPassword' => 'required|confirmed|',
            'newPassword' => new NoSamePassword
        ];

        //Comprueba que la contrasea sea la del usuario para actualizar los datos
        if(!Hash::check($data['oldPassword'], $user->password)){
            return response()->json(['message' => 'Password incorrect'],401);
        }

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        if($validation->fails())
        {
            return response()->json([
                'message' => $validation->errors(),
            ],422);
        }

        //Actualizamos la contraseña
        $user->update([
            'password' => Hash::make($request->newPassword)
        ]);


        return response([
            'userData' => $user,
            'password' => $user->password,
            'request' => $request->all()
        ],200);
    }

    public function addRole($userID, $roleID)
    {
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        $adminFound = false;

        foreach ($roles as $role) {
            if ($role->name === 'Admin') {
                $adminFound = true;
                break; // Sal del bucle si se encuentra el rol "Admin"
            }
        }

        if (!$adminFound) {
            return response()->json([
                'message' => 'You don\'t have the right permissions.',
            ], 401);
        }

        $user = User::find($userID);
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }

        $role = Role::find($roleID);
        if(!$role){
            return response()->json(['message' => 'Role not found'], 404);
        }

        foreach($user->roles as $userRoles){
            if($userRoles->name === $role->name){
                return response()->json(['message' => 'This user have this role'], 403);
            }
        }
        

        $user->roles()->attach($roleID);

        return response([
            'message' => 'Role added to the user succesffully'
        ],200);
    }

    public function removeRole($userID, $roleID)
    {
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        $adminFound = false;

        foreach ($roles as $role) {
            if ($role->name === 'Admin') {
                $adminFound = true;
                break; // Sal del bucle si se encuentra el rol "Admin"
            }
        }

        if (!$adminFound) {
            return response()->json([
                'message' => 'You don\'t have the right permissions.',
            ], 401);
        }

        $user = User::find($userID);
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }

        $role = Role::find($roleID);
        if(!$role){
            return response()->json(['message' => 'Role not found'], 404);
        }

        $findRole = false;
        foreach($user->roles as $userRoles){
            if($userRoles->name === $role->name){
                $findRole = true;
                break;
            }
        }

        if(!$findRole){
            return response()->json(['message' => 'User dont have this role'], 404);
        }

        $user->roles()->detach($roleID);

        return response([
            'message' => 'Role removed from the user succesffully'
        ],200);
    }

    public function addOrganization($userID, $organizationID)
    {
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        $adminFound = false;

        foreach ($roles as $role) {
            if ($role->name === 'Admin') {
                $adminFound = true;
                break; // Sal del bucle si se encuentra el rol "Admin"
            }
        }

        if (!$adminFound) {
            return response()->json([
                'message' => 'You don\'t have the right permissions.',
            ], 401);
        }

        $user = User::find($userID);
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }
        $org = Organization::find($organizationID);
        if(!$org){
            return response()->json(['message' => 'Organization not found'], 404);
        }

        foreach($user->organizations as $userOrgs){
            if($userOrgs->name === $org->name){
                return response()->json(['message' => 'This user already have this organization'], 403);
            }
        }

        $user->organizations()->attach($organizationID);

        return response([
            'message' => 'Organization added to the user succesffully'
        ],200);
    }

    public function removeOrganization($userID, $organizationID)
    {
        $rolesCurrentUser = Auth::user();
        $roles = $rolesCurrentUser->roles;

        $adminFound = false;

        foreach ($roles as $role) {
            if ($role->name === 'Admin') {
                $adminFound = true;
                break; // Sal del bucle si se encuentra el rol "Admin"
            }
        }

        if (!$adminFound) {
            return response()->json([
                'message' => 'You don\'t have the right permissions.',
            ], 401);
        }

        $user = User::find($userID);
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }
        $org = Organization::find($organizationID);
        if(!$org){
            return response()->json(['message' => 'Organization not found'], 404);
        }

        $findOrg = false;
        foreach($user->organizations as $userOrg){
            if($userOrg->name === $org->name){
                $findRole = true;
                break;
            }
        }

        if(!$findOrg){
            return response()->json(['message' => 'User dont have this organization'], 404);
        }

        $user->organizations()->detach($organizationID);

        return response([
            'message' => 'Organization removed from the user succesffully'
        ],200);
    }
}
