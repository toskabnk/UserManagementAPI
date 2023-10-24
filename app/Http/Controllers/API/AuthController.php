<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends ResponseController
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

        //Si la validacion falla, respondemos con los errores
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        //Guardamos los datos validados
        $userData = $validation->validated();
        
        //Guardamos la contraseña hasheada
        $userData['password'] = Hash::make($request->password);

        //Creamos el usuario
        $user = User::create($userData);

        //Creamos el token de acceso
        $accessToken = $user->createToken('authToken')->accessToken;

        //Creamos la estructura de la respuesta
        $response = [
            'message' => 'User created.',
            'token' => $accessToken
        ];

        //Enviamos la respuesta con los datos
        return $this->respondSuccess($response, 201);
    }

    public function login(Request $request)
    {
        //Validamos los datos
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        //Comprobamos las credenciales
        if(!auth()->attempt($loginData)) {
            return $this->respondUnauthorized('Invalid credentials.');
        }
        
        //Consiguimos el usuario y creamos el token
        /** @var \App\Models\User */
        $currentUser = Auth::user();
        $accessToken = $currentUser->createToken('authToken')->accessToken;

        //Creamos la estructura de la respuesta
        $response = [
            'user' => $currentUser,
            'token' => $accessToken
        ];

        //Enviamos la respuesta con los datos
        return $this->respondSuccess($response, 200);
    }

    public function logout()
    {
        $currentUser = Auth::user();

        if(!$currentUser){
            return $this-> respondUnauthorized();
        }

        $token = $currentUser->token();
        $token->revoke();

        return $this->respondSuccess(['message' => 'User logout']);
    }
}
