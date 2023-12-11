<?php

namespace App\Services;

use App\Exceptions\NotFound;
use App\Exceptions\Unauthorized;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user
     *
     * @param  \Illuminate\Http\Request $request
     * @param  array $userData
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request, $userData)
    {
        //Guardamos la contraseña hasheada
        $userData['password'] = Hash::make($request->password);

        //Organizacion
        $org = null;

        //Comprobamos si se ha pasado un Client por parametro
        if(!$request->has('idClient')){
            $org = Organization::where('name', 'Public')->get();
        } else {
            //Si el cliente no existe devolvemos un error
            //? Si no existe, añadimos a un Client por defecto?
            $client = Client::find($request->idClient);
            if(!$client){
                throw new NotFound('Client not found.');
            }

            //Si no tiene una organizacion por defecto, Public por defecto
            $clientDefaultOrg = Organization::find($client->default_organization);
            if(!$clientDefaultOrg){
                $org = Organization::where('name', 'Public')->get()->id;
            } else {
                $org = $clientDefaultOrg;
            }
        }

        //Creamos el User y el Member
        $user = User::create($userData);
        $userData['user_id'] = $user->id;
        $member = Member::create($userData);

        //Asociamos el Member a la Organizacion
        $member->organizations()->attach($org);

        //Asociamos el member al rol de Student por defecto
        //? Es adecuado hardcodear el rol student?
        $member->roles()->attach(3);

        //Creamos el token de acceso
        $accessToken = $user->createToken('authToken')->accessToken;

        //Creamos la estructura de la respuesta
        $response = [
            'message' => 'User created.',
            'token' => $accessToken
        ];

        //Enviamos la respuesta con los datos
        return $response;
    }

    public function login($loginData)
    {
        //Comprobamos las credenciales
        if(!auth()->attempt($loginData)){
            throw new Unauthorized('Invalid credentials.');
        }

        //Consiguimos el usuario y creamos el token
        /** @var \App\Models\User */
        $currentUser = Auth::user();
        $currentUser->client;
        $currentUser->member;
        $accessToken = $currentUser->createToken('authToken')->accessToken;

        $roles = null;
        if($currentUser->client){
            $roles = $currentUser->client->roles;
        } else {
            $roles = $currentUser->member->organizations;
        }

        //Creamos la estructura de la respuesta
        $response = [
            'user' => $currentUser,
            'roles' => $roles,
            'token' => $accessToken,
        ];

        //Enviamos la respuesta con los datos
        return $response;
    }

    public function profile($currentUser)
    {
        if(!$currentUser){
            throw new Unauthorized();
        }

        $currentUser->client;
        $currentUser->member;

        if($currentUser->client){
            $currentUser->client->load('roles', 'organizations');
        } else {
            $currentUser->member->load('roles', 'organizations');
        }

        return $currentUser;
    }
}

