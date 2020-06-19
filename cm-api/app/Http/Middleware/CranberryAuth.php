<?php

namespace App\Http\Middleware;

use Closure;

class CranberryAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth_token = $request->session()->get('auth_token', '');
        $app_token = $request->cookie('app_auth');
        if($auth_token === $app_token) {
            return $next($request);
        } else {
            return response()->json(['success' => false, 'force_logout' => true, 'error' => __('Invalid Access.')]); 
        }
    }
}