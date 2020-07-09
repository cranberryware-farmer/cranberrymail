<?php
/**
 * Main Controller
 * 
 * PHP Version 7.3
 * 
 * @category Controller
 * @package  CranberryMail
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Main Controller from which all controller should be inherited
 * 
 * @category Class
 * @package  Cranberrymail
 * @author   Ayus Mohanty <ayus.mohanty@nettantra.net>
 * @license  GNU AGPL-3.0
 * @link     https://cranberrymail.com
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Rewrites ENV variables in the process of installation and upgrade
     *
     * @param string $driver      Driver Value
     * @param string $environment Environment Value
     *
     * @return void
     */
    protected function controlSessionDriver($driver, $environment)
    {
        $envPath = base_path() . '/cmail_settings/.env';
        if (file_exists($envPath)) {
            $lines = file($envPath);
            $result = '';

            foreach ($lines as $line) {
                if (strpos($line, 'SESSION_DRIVER=') === 0) {
                    $result .= "SESSION_DRIVER=" . $driver . "\n";
                } else if (strpos($line, 'APP_ENV=') === 0) {
                    $result .= "APP_ENV=" . $environment . "\n";
                } else {
                    $result .= $line;
                }
            }

            file_put_contents($envPath, $result);
        }
    }
}
