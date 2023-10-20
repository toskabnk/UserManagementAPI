<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
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
            return response()->json([
                'message' => $validation->errors(),
            ],422);
        }

        $orgData = $validation->validated();

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

        $org = Organization::create($orgData);
        return response([
            'message' => 'Organization created successfully.',
            'organization' => $org
        ]);
    }

    public function view($id)
    {
        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return response()->json(['message' => 'Organization not found'], 404);
        }

        return response([
            'organization' => $org
        ]);
    }

    public function viewAll(Request $request){
        //Consigue todos los roles de la BD
        $org = Organization::all();

        return response([
            'organization' => $org
        ]);
    }

    public function update(Request $request, $id)
    {
        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return response()->json(['message' => 'Role not found'], 404);
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
            return response()->json([
                'message' => $validation->errors(),
            ],422);
        }

        $orgData = $validation->validated();

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

        $org->update($orgData);

        return response([
            'message' => 'Organization edited succesfully.',
            'organization' => $org
        ]);
    }

    public function remove($id)
    {
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

        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return response()->json(['message' => 'Organization not found'], 404);
        }


        $org->delete();

        return response([
            'message' => 'Role deleted successfully.'
        ]);
    }

    public function getUserFromOrg($id){
        $org = Organization::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$org){
            return response()->json(['message' => 'Organization not found'], 404);
        }

        $users = $org->users;

        return response([
            'organization' => $org,
            'users' => $users
        ]);
    }

    public function getOrgFromUser($id){
        $user = User::find($id);

        //Si no lo encuentra mandamos un 404
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }

        $orgs = $user->organizations;

        return response([
            'user' => $user,
            'organizations' => $orgs
        ]);
    }
}
