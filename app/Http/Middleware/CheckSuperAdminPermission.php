<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\ResponseController;
use App\Utils\CheckPermission as UtilsCheckPermission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSuperAdminPermission extends ResponseController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       //Conseguimos el usuario actual
       $currentUser = Auth::user();

       //Check if not null
       if(!$currentUser){
           return $this-> respondUnauthorized();
       }

       //Comprobamos si el usuario es un Client
       if(!$currentUser->client){
           return $this-> respondUnauthorized();
       }

       //Comprobamos si es un SuperAdmin
       if(!UtilsCheckPermission::checkSuperAdminPermision($currentUser->client->roles)){
           return $this->respondUnauthorized();
       }

       return $next($request);
    }
}
