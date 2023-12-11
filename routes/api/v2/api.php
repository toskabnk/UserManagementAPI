<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\OrganizationController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('/register/{clientID}', [ClientController::class, 'getClientName']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

//Rutas protegidas con autenticacion
Route::group(['middleware' => 'auth:api'], function(){

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/password', [UserController::class, 'changePassword']);

    Route::group(['prefix' => 'client'], function(){
        Route::get('/', [ClientController::class, 'viewAll'])->middleware('superAdmin');
        Route::get('/{clientID}', [ClientController::class, 'view'])->middleware('clientPermission');
        Route::post('/', [ClientController::class, 'create'])->middleware('superAdmin');
        Route::post('/{clientID}/role/{roleID}', [ClientController::class, 'addRole'])->middleware('superAdmin');
        Route::post('/{clientID}/organization/{organizationID}', [ClientController::class, 'addOrganization'])->middleware('superAdmin');
        Route::put('/{clientID}', [ClientController::class, 'update'])->middleware('clientPermission', 'isClient');
        Route::put('/{clientID}/role/{roleID}', [ClientController::class, 'modifyDefaultRole'])->middleware('clientPermission', 'isClient');
        Route::put('/{clientID}/organization/{organizationID}', [ClientController::class, 'modifyDefaultOrganization'])->middleware('clientPermission', 'isClient');
        Route::delete('/{clientID}', [ClientController::class, 'delete'])->middleware('superAdmin');
        Route::delete('/{clientID}/role/{roleID}', [ClientController::class, 'removeRole'])->middleware('superAdmin');
        Route::delete('/{clientID}/organization/{organizationID}', [ClientController::class, 'removeOrganization'])->middleware('superAdmin');
    });

    Route::group(['prefix' => 'member'], function(){
        Route::get('/', [MemberController::class, 'viewAll'])->middleware('clientPermission','isClient');
        Route::get('/{memberID}', [MemberController::class, 'view'])->middleware('clientPermission','sameMember');
        Route::post('/', [MemberController::class, 'create'])->middleware('clientPermission', 'isClient');
        Route::post('/{memberID}/roles/', [MemberController::class, 'addRoles'])->middleware('clientPermission', 'isClient');
        Route::post('/{memberID}/organizations/', [MemberController::class, 'addOrganization'])->middleware('clientPermission', 'isClient');
        Route::put('/{memberID}', [MemberController::class, 'update'])->middleware('clientPermission');
        Route::delete('/{memberID}', [MemberController::class, 'remove'])->middleware('clientPermission', 'isClient');
        Route::delete('/{memberID}/role/{roleID}', [MemberController::class, 'removeRole'])->middleware('clientPermission', 'isClient');
        Route::delete('/{memberID}/organization/{organizationID}', [MemberController::class, 'removeOrganization'])->middleware('clientPermission', 'isClient');
    });

    Route::group(['prefix' => 'user'], function(){
        Route::put('/{id}/password', [UserController::class, 'changePassword']);
    });

    Route::group(['prefix' => 'role'], function(){
        Route::get('/', [RoleController::class, 'viewAll'])->middleware('clientPermission', 'isClient');
        Route::get('/{roleId}', [RoleController::class, 'view'])->middleware('clientPermission');
        Route::get('/users/{roleId}', [RoleController::class, 'view'])->middleware('clientPermission');
        Route::post('/', [RoleController::class, 'create'])->middleware('clientPermission', 'isClient');
        Route::put('/{roleId}', [RoleController::class, 'update'])->middleware('clientPermission', 'isClient');
        Route::delete('/{roleId}', [RoleController::class, 'remove'])->middleware('clientPermission', 'isClient');
    });

    Route::group(['prefix' => 'organization'], function(){
        Route::get('/', [OrganizationController::class, 'viewAll'])->middleware('clientPermission');
        Route::get('/{organizationId}', [OrganizationController::class, 'view'])->middleware('clientPermission');
        Route::get('/{organizationId}/members', [OrganizationController::class, 'getUsersFromOrg'])->middleware('clientPermission', 'isClient');
        Route::get('/member/{memberId}', [OrganizationController::class, 'getOrgsFromUser'])->middleware('clientPermission', 'isClient');
        Route::post('/', [OrganizationController::class, 'create'])->middleware('clientPermission', 'isClient');
        Route::put('/{organizationId}', [OrganizationController::class, 'update'])->middleware('clientPermission', 'isClient');
        Route::delete('/{organizationId}', [OrganizationController::class, 'delete'])->middleware('clientPermission', 'isClient');
    });
});
