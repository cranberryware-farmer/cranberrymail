<?php
/**
 * Main Controller
 *
 * PHP Version 7.3
 *
 * @category Productivity
 * @package  CranberryMail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Main Controller from which all controller should be inherited
 *
 * @category Controller
 * @package  Cranberrymail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Laravel Auth User Details
     */
    protected $user;

    /**
     * Force Logout Response
     *
     * @var array
     */
    protected $forceLogout = [];

    /**
     * Main constructor.
     */
    public function __construct()
    {
        $this->forceLogout = [
            'success' => false,
            'force_logout' => true,
            'error' => __('Invalid Access.')
        ];
    }

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

    /**
     * Create IMAP Object
     * 
     * @param Illuminate\Http\Request $req Laravel Request
     * 
     * @return bool|\Horde_Imap_Client_Socket
     * @throws \Horde_Imap_Client_Exception
     */
    protected function getIMAPCredential($req)
    {
        $user = Auth::user();
        if ($user && isset($user->email)) {
            $this->user = $user;
            $email = $user->email;
            $password_key = $req->session()->get('password_key', '');
            $password=Crypt::decryptString($password_key);

            return $this->loginIMAPClient($email, $password);
        }
        return false;
    }

    /**
     * Logs in to IMAP
     * 
     * @param string $email    IMAP email id
     * @param string $password Password
     * 
     * @return \Horde_Imap_Client_Socket
     * @throws \Horde_Imap_Client_Exception
     */
    protected function loginIMAPClient($email, $password): \Horde_Imap_Client_Socket
    {
        $client = new \Horde_Imap_Client_Socket(
            [
                'username' => $email,
                'password' => $password,
                'hostspec' => env('IMAP_HOST'),
                'port' => env('IMAP_PORT'),
                'secure' => env("IMAP_ENCRYPTION") //ssl,tls etc
            ]
        );
        Log::info("oClient created", ['file' => __FILE__, 'line' => __LINE__]);
        $client->login();
        Log::info(
            "Login with oClient",
            ['file' => __FILE__, 'line' => __LINE__]
        );

        return $client;
    }
}
