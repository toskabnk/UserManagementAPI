<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

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
Route::put('user/update/{id}', [AuthController::class, 'updateUser'])->middleware('auth:api');
Route::get('user/{id}', [AuthController::class, 'getUser'])->middleware('auth:api');
Route::delete('user/{id}', [AuthController::class, 'deleteUser'])->middleware('auth:api');