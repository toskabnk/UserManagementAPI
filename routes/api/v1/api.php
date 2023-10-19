<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RoleController;

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

Route::post('user/register', [AuthController::class, 'register']);
Route::post('user/login', [AuthController::class, 'login']);

Route::prefix('user')->middleware('auth:api')->group(function (){
    Route::put('update/{id}', [AuthController::class, 'updateUser']);
    Route::get('{id}', [AuthController::class, 'getUser']);
    Route::delete('{id}', [AuthController::class, 'deleteUser']);
    Route::put('password/{id}', [AuthController::class, 'changePassword']);
    Route::post('/{userID}/role/{roleID}', [AuthController::class, 'addRole']);
    Route::delete('/{userID}/role/{roleID}', [AuthController::class, 'deleteRole']);
});

Route::prefix('role')->middleware('auth:api')->group(function (){
    Route::get('', [RoleController::class, 'viewAll']);
    Route::put('{id}', [RoleController::class, 'update']);
    Route::get('{id}', [RoleController::class, 'view']);
    Route::delete('{id}', [RoleController::class, 'delete']);
    Route::post('', [RoleController::class, 'create']);
    Route::get('{id}/users', [RoleController::class, 'getUsersFromRole']);
});

