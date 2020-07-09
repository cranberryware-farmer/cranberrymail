<?php
/**
 * Custom middleware for authentication
 * 
 * PHP Version 7.3
 * 
 * @category Middleware
 * @package  CranberryMail
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */
namespace App\Http\Middleware;

use Closure;

/**
 * Middleware Class
 * 
 * @category Class
 * @package  Cranberrymail
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */
class CranberryAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request Laravel Request
     * @param \Closure                 $next    Passon variable
     * 
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth_token = $request->session()->get('auth_token', '');
        $app_token = $request->cookie('app_auth');
        if ($auth_token === $app_token) {
            return $next($request);
        } else {
            $request->session()->flush();
            return response()->json(
                [
                    'success' => false,
                    'force_logout' => true,
                    'error' => __('Invalid Access.')
                ]
            );
        }
    }
}
