<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\ResponseController;
use App\Utils\CheckPermission as UtilsCheckPermission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission extends ResponseController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //Conseguimos el usuario de la BD
        $currentUser = Auth::user();

        //Comprobamos si existe
        if(!$currentUser) {
            return $this->respondUnauthorized();
        }

        $superAdmin = false;
        $admin = false;
        $sameClientID = false;
        $sameMemberID = false;
        $isClient = false;
        $isMember = false;

        if($currentUser->client){
            $superAdmin = UtilsCheckPermission::checkSuperAdminPermision($currentUser->client->roles);
            if(!$superAdmin){
                $admin = UtilsCheckPermission::checkAdminPermision($currentUser->client->roles);
            }
            $isClient = true;
            //Si pasamos un clientID por el paramatro de la ruta, comprobamos que sean iguales que el del usuario actual
            if($request->route('clientID') && $request->route('clientID') == $currentUser->client->id){
                $sameClientID = true;
            }
        } else {
            if($currentUser->member){
                $isMember = true;
                if($request->route('memberID') && $request->route('memberID') == $currentUser->member->id){
                    $sameMemberID = true;
                }
            }
        }

        $request->attributes->add(['currentUser' => $currentUser]);
        $request->attributes->add(['superAdmin' => $superAdmin]);
        $request->attributes->add(['admin' => $admin]);
        $request->attributes->add(['sameClientID' => $sameClientID]);
        $request->attributes->add(['sameMemberID' => $sameMemberID]);
        $request->attributes->add(['isClient' => $isClient]);
        $request->attributes->add(['isMember' => $isMember]);

        return $next($request);
    }
}
