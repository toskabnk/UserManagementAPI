<?php

namespace App\Http\Controllers\API;

use App\Rules\NoSamePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends ResponseController
{

    public function changePassword(Request $request)
    {
        //Conseguimos el usuario
        /** @var \App\Models\User */
        $user = Auth::user();

        //Comprobamos si existe
        if (!$user) {
            return $this->respondNotFound('User not found.');
        }

        $data = $request->all();

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
}
