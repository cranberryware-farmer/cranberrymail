<?php
/**
 * Custom middleware for authentication
 * 
 * PHP Version 7.3
 * 
 * @category Productivity
 * @package  CranberryMail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
namespace App\Http\Middleware;

use Closure;

/**
 * Middleware Class
 * 
 * @category MiddleWare
 * @package  Cranberrymail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
class CranberryAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request Laravel Request
     * @param \Closure                 $next    Pass-on variable
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
