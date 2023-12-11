<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\ResponseController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfClient extends ResponseController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isClient = $request->attributes->get('isClient');

        //Comprobamos que usuario actual sea un Client
        if(!$isClient){
            return $this->respondUnauthorized();
        }

        return $next($request);
    }
}
