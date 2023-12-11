<?php

namespace App\Http\Controllers\API;

use App\Exceptions\NotFound;
use App\Exceptions\Unauthorized;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends ResponseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
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

        try {
            //Iniciamos la transaccion
            DB::beginTransaction();

            //LLamar al servicio de registro
            $response = $this->authService->register($request, $userData);

            //Commit de la transaccion
            DB::commit();

            //Enviamos la respuesta con los datos
            return $this->respondSuccess($response, 201);

        } catch (NotFound $nf) {
            //Rollback de la transaccion y respondemos con el tipo de error
            DB::rollBack();
            return $this->respondNotFound($nf->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    public function login(Request $request)
    {
        //Validamos los datos
        $rules = [
            'email' => 'email|required',
            'password' => 'required'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Si la validacion falla, respondemos con los errores
        if($validation->fails()){
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        //Guardamos los datos validados
        $loginData = $validation->validated();

        try {
            //LLamar al servicio de login
            $response = $this->authService->login($loginData);

            //Enviamos la respuesta con los datos
            return $this->respondSuccess($response, 200);

        } catch (Unauthorized $u) {
            //Respondemos con el tipo de error
            return $this->respondUnauthorized($u->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondInternalServerError($e->getMessage());
        }

        //Enviamos la respuesta con los datos
        return $this->respondSuccess($response, 200);
    }

    public function logout()
    {
        $currentUser = Auth::user();

        if(!$currentUser){
            return $this->respondUnauthorized();
        }

        $token = $currentUser->token();
        $token->revoke();

        return $this->respondSuccess(['message' => 'User logout']);
    }

    public function profile()
    {
        //Usuario actual
        $currentUser = Auth::user();

        try {
            //LLamar al servicio de profile
            $profile = $this->authService->profile($currentUser);

            //Enviamos la respuesta con los datos
            return $this->respondSuccess(['user' => $profile]);

        } catch (Unauthorized $u) {
            //Respondemos con el tipo de error
            return $this->respondUnauthorized($u->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    public function me()
    {
        $currentUser = Auth::user();

        if(!$currentUser){
            return $this-> respondUnauthorized();
        }

        return $this->respondSuccess(['user' => $currentUser]);
    }
}
