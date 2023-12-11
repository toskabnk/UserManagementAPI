<?php

namespace App\Http\Controllers\API;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrganizationController extends ResponseController
{
    protected $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * Create a new Organization
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //Middleware CheckPermission isClient
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Reglas de validacion
        $rules = [
            'name' => 'required|unique:organizations|min:3',
            'description' => 'sometimes|required',
            'client_id' => 'sometimes|required|exists:clients,id'
        ];

        //Comprobamos si tiene los permisos necesarios
        if(!$admin && !$superAdmin){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

         //Comprobamos el resultado de la validacion
        if($validation->fails()){
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $orgData = $validation->validated();
        try{
            $this->organizationService->create($orgData, $currentUser, $superAdmin);
            return $this->respondSuccess(['message' => 'Organization created.'], 201);
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * View a Organization
     *
     * @param Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function view($organizationId)
    {
        //Middleware CheckPermission
        try{
            $org = $this->organizationService->view($organizationId);
            return $this->respondSuccess(['organization' => $org]);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * View all Organizations
     *
     * @return \Illuminate\Http\Response
     */
    public function viewAll(Request $request){
        try{
            $orgs = $this->organizationService->viewAll($request);
            return $this->respondSuccess(['organizations' => $orgs]);
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * Update a Organization
     *
     * @param Request $request
     * @param int $id
     */
    public function update(Request $request, $organizationId)
    {
        //Reglas de validacion
        $rules = [
            'name' => ['sometimes','required','min:3',Rule::unique('organizations')->ignore($organizationId)],
            'description' => 'sometimes|required',
            'client_id' => 'sometimes|required|exists:clients,id',
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        //Gaurdamos los datos validados
        $orgData = $validation->validated();

        try{
            $org = $this->organizationService->update($request, $organizationId, $orgData);

            $response = [
                'message' => 'Organization modified.',
                'organization' => $org
            ];

            return $this->respondSuccess($response);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * Delete a Organization
     *
     * @param Request $request
     * @param int $organizationId
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function delete(Request $request, $organizationId)
    {
        //Middleware CheckPermission isClient
        try{
            $this->organizationService->remove($request, $organizationId);
            return $this->respondSuccess(['message' => 'Organization deleted.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * Get Users from a Organization with the given id
     *
     * @param Request $request
     * @param int $organizationId
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function getUsersFromOrg(Request $request, $organizationId){
        //Middleware CheckPermission, isClient
        try{
            $response = $this->organizationService->getUsers($request, $organizationId);
            return $this->respondSuccess($response);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * Get the Organizations from the User with the given id
     *
     * @param Request $request
     * @param int $organizationId
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function getOrgsFromUser(Request $request, $memberId){
        //Middleware CheckPermission, isClient
        try{
            $response = $this->organizationService->getOrgsFromUser($request, $memberId);
            return $this->respondSuccess($response);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }
}
