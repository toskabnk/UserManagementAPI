<?php

namespace App\Http\Controllers\API;

use App\Exceptions\Forbidden;
use App\Exceptions\NotFound;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MemberService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MemberController extends ResponseController
{
    protected $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * Check if the roles and organizations exist
     *
     * @param array $userData
     *
     * @return null|array
     */
    protected function checkRolesOrganizationsExist($userData)
    {
        //Comprobamos si existen las organizaciones y los roles
        if(isset($userData['organizations'])){
            $response = $this->checkOrganizations($userData['organizations']);
            if($response && !empty($response['errors'])){
                return $response;
            }
        }

        if(isset($userData['roles'])){
            $response = $this->checkRoles($userData['roles']);
            if($response && !empty($response['errors'])){
                return $response;
            }
        }

        return null;
    }

    /**
     * Check if the organizations exist
     *
     * @param array $organizationsData
     *
     * @return null|array
     */
    protected function checkOrganizations($organizationsData)
    {
        $errors = [];
        foreach($organizationsData as $orgId){
            $org = Organization::find($orgId);
            if(!$org){
                array_push($errors, 'Orgnization with id: '. $orgId . ' not found');
            }
        }

        $response = [
            'message' => 'Organization not found.',
            'errors' => $errors
        ];

        if(!empty($errors)){
            return $response;
        }
    }

    /**
     * Check if the roles exist
     *
     * @param array $rolesData
     *
     * @return null|array
     */
    protected function checkRoles($rolesData)
    {
        $errors = [];
        foreach($rolesData as $roleId){
            $role = Role::find($roleId);
            if(!$role){
                array_push($errors, 'Role with id '. $roleId . ' not found');
            }
        }

        $response = [
            'message' => 'Role not found.',
            'errors' => $errors
        ];

        if(!empty($errors)){
            return $response;
        }

        return null;
    }

    /**
     * Create a new Member
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //Middleware CheckPermission
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Reglas de validacion
        $rules = [
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'birth_date' => 'required|date_format:Y-m-d|before:today|after:1900-01-01',
            'email' => 'required|email|unique:users',
            'password_confirmation' => 'required|same:password',
            'password' => 'required|confirmed',
            'organizations' => '',
            'roles' => ''
        ];

        //Validacion del parametro $request, con las reglas y los mensajes personalizados
        $validation = Validator::make($request->all(),$rules, config('custom_validation_messages'));

        //Si la validacion falla, respondemos con los errores
        if($validation->fails()){
            return $this->respondUnprocessableEntity('Validation errors', $validation->errors());
        }

        //Guardamos los datos validados
        $userData = $validation->validated();

        //Comprobamos si existen las organizaciones y los roles
        $response = $this->checkRolesOrganizationsExist($userData);
        if($response && !empty($response['errors'])){
            return $this->respondNotFound($response['message'], $response['errors']);
        }

        //Guardamos la contraseña hasheada
        $userData['password'] = Hash::make($request->password);

        DB::beginTransaction();
        try {
            //Creamos el usuario
            $this->memberService->createMember($userData, $currentUser, $superAdmin, $admin);
            DB::commit();
        } catch (Forbidden $f) {
            DB::rollback();
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return $this->respondInternalServerError($e->getMessage());
        }

        //Creamos la estructura de la respuesta
        $response = [
            'message' => 'User created.'
        ];

        //Enviamos la respuesta con los datos
        return $this->respondSuccess($response, 201);
    }

    /**
     * Update the specified Member
     *
     * @param Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $memberID)
    {
        //Middleware CheckPermission
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');
        $sameMemberID = $request->attributes->get('sameMemberID');
        $isClient = $request->attributes->get('isClient');

        //Comprobamos si es un Client o un Member
        if(!$isClient){
            if(!$sameMemberID){
                return $this->respondUnauthorized('You are trying to update a member that is not associated with you.');
            }
        }

        //Comprobamos si el Member existe
        $member = Member::find($memberID);
        if(!$member){
            return $this->respondNotFound('Member not found.');
        }

        //Reglas de validacion
        $rules = [
            'name' => 'sometimes|required|max:255',
            'surname' => 'sometimes|required|max:255',
            'birth_date' => 'sometimes|required|date_format:Y-m-d|before:today|after:1900-01-01',
            'email' => ['email',Rule::unique('users')->ignore($member->user_id)],
            'organizations' => '',
            'roles' => ''
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

        //Si es un Client, comprobamos si las organizaciones y los roles existen
        if($isClient){
            //Comprobamos si existen las organizaciones y los roles
            $response = $this->checkRolesOrganizationsExist($userData);
            if($response && !empty($response['errors'])){
                return $this->respondNotFound($response['message'], $response['errors']);
            }
        }

        //Actualizamos el Member y el User
        DB::beginTransaction();
        try {
            $this->memberService->updateMember($userData, $currentUser, $superAdmin, $admin, $member);
            DB::commit();

            return $this->respondSuccess(['message' => 'Member updated.']);
        } catch (Forbidden $f) {
            DB::rollback();
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * Add the Role to the Member
     *
     * @param Request $request
     * @param int $memberID
     *
     * @return \Illuminate\Http\Response
     */
    public function addRoles(Request $request, $memberID)
    {
        //Middleware CheckPermission
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Comprobamos que existe el Member, el Role y la Organization
        $member = Member::find($memberID);
        if(!$member){
            return $this->respondNotFound('Member not found.');
        }

        //Comprobamos si existen los roles
        $response = $this->checkRoles($request['roles']);
        if($response && !empty($response['errors'])){
            return $this->respondNotFound($response['message'], $response['errors']);
        }

        //Comprobamos si tiene el rol admin o superadmin
        if(!$admin && !$superAdmin){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        //Añadimos el rol a el usuario
        try{
            $this->memberService->addRolesToMember($request->all(),$currentUser,$superAdmin, $admin, $member);
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }

        return $this->respondSuccess(['message' => 'Role added to the user.']);
    }

    /**
     * Remove the Role to the Member
     *
     * @param Request $request
     * @param int $memberID
     * @param int $roleID
     * @param int $organizationID
     *
     * @return \Illuminate\Http\Response
     */
    public function removeRole(Request $request, $memberID, $roleID)
    {
        //Middleware CheckPermission
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Comprobamos que existe el Member y el Role
        $member = Member::find($memberID);
        if(!$member){
            return $this->respondNotFound('Member not found.');
        }

        $role = Role::find($roleID);
        if(!$role){
            return $this->respondNotFound('Role not found.');
        }

        if(!$admin && !$superAdmin){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        try{
            $this->memberService->removeRoleFromMember($roleID,$currentUser,$superAdmin, $admin, $member);
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }

        return $this->respondSuccess(['message' => 'Role removed from the user.']);
    }

    /**
     * Add the Organization to the Member
     *
     * @param int $memberID
     * @param int $organizationID
     *
     * @return \Illuminate\Http\Response
     */
    public function addOrganization(Request $request, $memberID)
    {
        //Middleware CheckPermission
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Comprobamos que existe el Member
        $member = Member::find($memberID);
        if(!$member){
            return $this->respondNotFound('Member not found.');
        }

        //Comprobamos si existen las organizaciones
        $response = $this->checkOrganizations($request['organizations']);
        if($response && !empty($response['errors'])){
            return $this->respondNotFound($response['message'], $response['errors']);
        }

        if(!$admin && !$superAdmin){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        try{
            $this->memberService->addOrganizationToMember($request->all(),$currentUser,$superAdmin, $admin, $member);
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }

        return $this->respondSuccess(['message' => 'Organization added to the member.']);
    }

    /**
     * Remove the Organization to the Member
     *
     * @param Request $request
     * @param int $memberID
     * @param int $organizationID
     *
     * @return \Illuminate\Http\Response
     */
    public function removeOrganization(Request $request, $memberID, $organizationID)
    {
        //Middleware CheckPermission
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Comprobamos que existe el Member y la Organization
        $member = Member::find($memberID);
        if(!$member){
            return $this->respondNotFound('Member not found.');
        }

        $organization = Organization::find($organizationID);
        if(!$organization){
            return $this->respondNotFound('Organization not found.');
        }

        if(!$admin && !$superAdmin){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        try{
            $this->memberService->removeOrganizationFromMember($organization,$currentUser,$superAdmin, $admin, $member);
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }

        return $this->respondSuccess(['message' => 'Organization removed from the member.']);
    }

    /**
     * Remove the specified Member
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function remove(Request $request, $memberID)
    {
        //Middleware CheckPermission
        //Datos del Middleware
        $currentUser = $request->attributes->get('currentUser');
        $superAdmin = $request->attributes->get('superAdmin');
        $admin = $request->attributes->get('admin');

        //Comprobamos si el Member existe
        $member = Member::find($memberID);
        if(!$member){
            return $this->respondNotFound('Member not found.');
        }

        //Comprobamos si tiene el rol admin o superadmin
        if(!$admin && !$superAdmin){
            return $this->respondUnauthorized('You don\'t have the right permissions.');
        }

        DB::beginTransaction();
        try{

            $this->memberService->removeMember($currentUser,$superAdmin, $admin, $member);
            DB::commit();

            //Respuesta vacia
            return response()->noContent();
        } catch (Forbidden $f) {
            DB::rollback();
            return $this->respondForbidden($f->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            //Respuesta de error
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * Get the specified Member
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request, $memberID)
    {
        //Middleware CheckPermission, sameMember
        try{
            $member = $this->memberService->viewMember($request, $memberID);
            return $this->respondSuccess(['member' => $member]);
        } catch (Forbidden $f) {
            return $this->respondForbidden($f->getMessage());
        } catch (NotFound $nf) {
            return $this->respondForbidden($nf->getMessage());
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    /**
     * Get all Members
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function viewAll(Request $request)
    {
        //Middleware checkPermission isClient

        $members = $this->memberService->viewAllMembers($request);
        //Enviamos la respuesta con los datos
        return $this->respondSuccess(['members' => $members]);
    }
}
