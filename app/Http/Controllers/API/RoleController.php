<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
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
            return response()->json([
                'message' => $validation->errors(),
            ],422);
        }

        $roleData = $validation->validated();

        /** @var \App\Models\User */
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


        $role = Role::create($roleData);

        return response([
            'message' => 'Role created successfully.',
            'role' => $role
        ]);
    }

    public function view($id){
        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response([
            'role' => $role
        ]);
    }

    public function viewAll(Request $request){
        //Consigue todos los roles de la BD
        $role = Role::all();

        return response([
            'roles' => $role
        ]);
    }

    public function update(Request $request, $id)
    {
        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return response()->json(['message' => 'Role not found'], 404);
        }

        $rules = [
            'name' => 'required|unique:roles|min:3'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return response()->json([
                'message' => $validation->errors(),
            ],422);
        }

        /** @var \App\Models\User */
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

        $role->update([
            'name' => $request->name
        ]);

        return response([
            'message' => 'Role edited succesfully.',
            'role' => $role
        ]);
    }

    public function remove($id)
    {
        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return response()->json(['message' => 'Role not found'], 404);
        }

        /** @var \App\Models\User */
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

        $role->delete();

        return response([
            'message' => 'Role deleted successfully.'
        ]);
    }

    public function getUsersFromRole($id){
        /** @var \App\Models\User */
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

        //Conseguimos el rol de la BD
        $role = Role::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return response()->json(['message' => 'Role not found'], 404);
        }

        $users = $role->users;

        return response([
            'role' => $role,
            'users' => $users
        ]);
    }
}
