<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

//Rutas protegidas con autenticacion
Route::group(['middleware' => 'auth:api'], function(){
    Route::group(['prefix' => 'user'], function(){
        Route::get('/', [UserController::class, 'viewAll']);
        Route::get('/{id}', [UserController::class, 'view']);
        Route::post('/', [UserController::class, 'create']);
        Route::post('/{userID}/organization/{organizationID}', [UserController::class, 'addOrganization']);
        Route::post('/{userID}/role/{roleID}', [UserController::class, 'addRole']);
        Route::put('/{id}', [UserController::class, 'updateUser']);
        Route::put('/{id}/password', [UserController::class, 'changePassword']);
        Route::delete('/{id}', [UserController::class, 'deleteUser']);
        Route::delete('/{userID}/role/{roleID}', [UserController::class, 'removeRole']);
        Route::delete('/{userID}/organization/{organizationID}', [UserController::class, 'removeOrganization']);
    });

    Route::group(['prefix' => 'role'], function(){
        Route::get('/', [RoleController::class, 'viewAll']);
        Route::get('/{id}', [RoleController::class, 'view']);
        Route::get('/{id}/users', [RoleController::class, 'getUsersFromRole']);
        Route::post('/', [RoleController::class, 'create']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'delete']);
    });

    Route::group(['prefix' => 'organization'], function(){
        Route::get('/', [OrganizationController::class, 'viewAll']);
        Route::get('{id}', [OrganizationController::class, 'view']);
        Route::get('{id}/users', [OrganizationController::class, 'getUserFromOrg']);
        Route::get('user/{id}', [OrganizationController::class, 'getOrgFromUser']);
        Route::post('/', [OrganizationController::class, 'create']);
        Route::put('/{id}', [OrganizationController::class, 'update']);
        Route::delete('/{id}', [OrganizationController::class, 'remove']);
    });
});