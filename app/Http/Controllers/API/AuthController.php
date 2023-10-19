<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
        $user = User::select('name', 'surname', 'email')->where('id',$id)->first();

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
}
