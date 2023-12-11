<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\ResponseController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSameMember extends ResponseController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMember = $request->attributes->get('isMember');
        $sameMemberID = $request->attributes->get('sameMemberID');

        //Comprobamos que el usuario sea un member
        if($isMember){
            //Comprobamos que el memberID de la ruta sea igual que el del usuario actual
            if(!$sameMemberID){
                return $this->respondUnauthorized();
            }
        }

        return $next($request);
    }
}
