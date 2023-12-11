<?php

namespace App\Http\Controllers\API;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleController extends ResponseController
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Create a new Role
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //Reglas de validacion
        $rules = [
            'name' => 'required|unique:roles|min:3',
            'clients' => 'sometimes|array',
            'clients.*' => 'integer|exists:clients,id',
            'members' => 'sometimes|array',
            'members.*' => 'integer|exists:members,id'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

         //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $roleData = $validation->validated();

        DB::beginTransaction();
        try{
            $this->roleService->create($request, $roleData);

            DB::commit();

            return $this->respondSuccess(['message' => 'Role created.']);
        } catch (NotFound $nf) {
            DB::rollBack();
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            DB::rollBack();
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondInternalError($e->getMessage());
        }
    }

    /**
     * View a Role
     *
     * @param int $roleId
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function view(Request $request, $roleId){
        try{
            $role = $this->roleService->view($request, $roleId);

            return $this->respondSuccess(['role' => $role]);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalError($e->getMessage());
        }
    }

    public function viewAll(Request $request){
        //Middleware checkPermission isClient
        try{
            $roles = $this->roleService->viewAll($request);
            return $this->respondSuccess(['roles' => $roles]);
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalError($e->getMessage());
        }
    }

    /**
     * Update a Role
     *
     * @param Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function update(Request $request, $roleId)
    {
        //Middleware checkPermission isClient
        //Conseguimos el rol de la BD
        $role = Role::find($roleId);

        //Si no lo encuentra mandamos un 404
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        //Reglas de validacion
        $rules = [
            'name' => ['required','min:3',Rule::unique('roles')->ignore($role->id)],
            'clients' => 'sometimes|array',
            'clients.*' => 'integer|exists:clients,id',
            'members' => 'sometimes|array',
            'members.*' => 'integer|exists:members,id'
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Comprobamos el resultado de la validacion
        if($validation->fails())
        {
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        $roleData = $validation->validated();

        DB::beginTransaction();
        try{
            $this->roleService->update($request, $role, $roleData);

            DB::commit();
            return $this->respondSuccess(['message' => 'Role updated.']);
        } catch (Forbidden $f) {
            DB::rollBack();
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondInternalError($e->getMessage());
        }
    }

    public function remove(Request $request, $roleId)
    {
        //Middleware checkPermission isClient
        try{
            $this->roleService->remove($request, $roleId);

            return $this->respondSuccess(['message' => 'Role removed.']);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalError($e->getMessage());
        }
    }

    public function getUsersFromRole(Request $request, $id){
        //Middleware checkPermission isClient
        try{
            $data = $this->roleService->getMembersFromRole($request, $id);

            return $this->respondSuccess(['users' => $data]);
        } catch (NotFound $nf) {
            return $this->respondNotFound($nf->getMessage());
        } catch (\Exception $e) {
            return $this->respondInternalError($e->getMessage());
        }
    }
}
