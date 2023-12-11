<?php

namespace App\Http\Controllers\API;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientController extends ResponseController
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Create a new Client with a User associated.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //Middleware CheckSuperAdminPermission
        //Reglas de validacion
        $rules = [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password_confirmation' => 'required|same:password',
            'password' => 'required|confirmed',
            'organization_name' => 'sometimes|required|max:255',
            'organization_description' => 'sometimes|required|max:255',
            'roles' => 'sometimes|array',
            'roles.*' => 'sometimes|integer|exists:roles,id'
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

        DB::beginTransaction();
        try{

            $response = $this->clientService->create($userData);
            DB::commit();

            return $this->respondSuccess($response, 201);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Update the specified Client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $clientID)
    {
        //Middleware CheckPermission
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $sameClientID = $request->attributes->get('sameClientID');

        //Conseguimos el usuario de la BD
        $client = Client::find($clientID);

        //Comprobamos si existe
        if(!$client) {
            return $this->respondNotFound('User not found');
        }

        //Comprobamos que es un superAdmin o el mismo usuario
        if(!$superAdmin){
            if(!$sameClientID){
                return $this->respondUnauthorized('Unauthorized');
            }
        }

        //Reglas de validacion
        $rules = [
            'name' => 'sometimes|required|max:255',
            'email' => ['required','email',Rule::unique('users')->ignore($client->id)],
            'password' => 'sometimes|required',
            'roles' => 'sometimes|array',
            'roles.*' => 'sometimes|integer|exists:roles,id',
            'password_change' => 'sometimes|required',
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules,config('custom_validation_messages'));

        //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $data = $validation->validated();

        //Si no es un superAdmin, la contraseña es obligatoria para cambiar los datos
        if(!$superAdmin){
            //Comprueba que la contrasea sea la del usuario para actualizar los datos
            if(!Hash::check($data['password'], $client->user->password)){
                return $this->respondUnauthorized('Invalid credentials.');
            }
        }

        //Actualizamos el usuario
        DB::beginTransaction();
        try{

            $response = $this->clientService->update($data, $client, $superAdmin);
            DB::commit();

            return $this->respondSuccess($response);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * View all the Clients.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewAll(Request $request){
        //Middleware CheckSuperAdminPermission

        $query = Client::query()->with(['user','roles','organizations']);

        //Comprobamos si se ha pasado algun parametro y añadimos la condicion a la query
        if($request->has('email')){
            $email = $request->input('email');
            $query->whereHas('user', function ($query) use ($email) {
                $query->where('email', 'like', '%' . $email . '%');
            });
        }

        if($request->has('name')){
            $name = $request->input('name');
            $query->where('name', 'like', '%' . $name . '%');
        }

        //Ejecutamos la query
        $user = $query->get();

        return $this->respondSuccess(['clients' => $user]);
    }

    /**
     * View the specified Client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request, $clientID)
    {
        //Middleware CheckPermission
        try{
            $client = $this->clientService->view($request, $clientID);
            return $this->respondSuccess(['client' => $client]);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Delete the specified Client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($clientID)
    {
        //Middleware CheckSuperAdminPermission
        DB::beginTransaction();
        try{
            $this->clientService->delete($clientID);
            DB::commit();

            //Respuesta vacia
            return response()->noContent();
        } catch (NotFound $nf) {
            DB::rollback();
            return $this->respondNotFound($nf->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Add the specified Role to the Client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addRole($clientID, $roleID)
    {
        //Middleware CheckSuperAdminPermission
        try{
            $this->clientService->addRoleToClient($clientID, $roleID);
            return $this->respondSuccess(['message' => 'Role added to the client.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Remove the specified Role from the Client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function removeRole($clientID, $roleID)
    {
        //Middleware CheckSuperAdminPermission
        try{
            $this->clientService->removeRoleFromClient($clientID, $roleID);
            return $this->respondSuccess(['message' => 'Role removed from the client.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Add the specified Organization to the Client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addOrganization($clientID, $organizationID)
    {
        //Middleware CheckSuperAdminPermission
        try{
            $this->clientService->addOrganizationToClient($clientID, $organizationID);
            return $this->respondSuccess(['message' => 'Organization added to the client.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Remove the specified Organization from the Client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function removeOrganization($clientID, $organizationID)
    {
        //Middleware CheckSuperAdminPermission
        try{
            $this->clientService->removeOrganizationFromClient($clientID, $organizationID);
            return $this->respondSuccess(['message' => 'Organization removed from the client.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Get the name of the specified Client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getClientName($clientID){
        $client = Client::find($clientID);

        if(!$client) {
            return $this->respondNotFound('Client not found');
        }

        return $this->respondSuccess(['client_name' => $client->name]);
    }

    /**
     * Modify the client default role.
     *
     * @param Request $request
     * @param int $clientID
     * @param int $roleID
     *
     * @return \Illuminate\Http\Response
     */
    public function modifyDefaultRole(Request $request, $clientID, $roleID){
        //Middleware isClient
        try{
            $this->clientService->modifyDefaultRole($request, $clientID, $roleID);
            return $this->respondSuccess(['message' => 'Default role modified.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }

    /**
     * Modify the client default organization.
     *
     * @param Request $request
     * @param int $clientID
     * @param int $organizationID
     *
     * @return \Illuminate\Http\Response
     */
    public function modifyDefaultOrganization(Request $request, $clientID, $organizationID){
        //Middleware isClient

        try{
            $this->clientService->modifyDefaultOrganization($request, $clientID, $organizationID);
            return $this->respondSuccess(['message' => 'Default organization modified.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalServerError($e->getMessage());
        }
    }
}
