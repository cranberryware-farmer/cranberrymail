<?php
/**
 * Implements Authentication functionality
 *
 * PHP Version 7.3
 *
 * @category Productivity
 * @package  CranberryMail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Validator;

/**
 * Implements all methods related to Authentication
 *
 * @category Controller
 * @package  Cranberrymail
 * @author   CranberryWare Development Team (NetTantra Technologies) <support@oss.nettantra.com>
 * @license  GNU AGPL-3.0 https://github.com/cranberryware/cranberrymail/blob/master/LICENSE
 * @link     https://github.com/cranberryware/cranberrymail
 */
class AuthController extends Controller
{
    /**
     * Success status variable
     *
     * @var int
     */
    private $_successStatus = 200;

    /**
     * Unauthorized status variable
     *
     * @var int
     */
    private $_unAuthStatus = 401;

    /**
     * Implements login
     *
     * @param Request $request Laravel Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info(
            "Entered login function",
            ['file' => __FILE__, 'line' => __LINE__]
        );

        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'password' => 'required'
            ]
        );

        if ($validator->fails()) {
            Log::error(
                "Error while validating your email or password",
                ['file' => __FILE__, 'line' => __LINE__]
            );
            return response()->json(
                ['error'=>$validator->errors(), 'status' => 0],
                $this->_successStatus
            );
        }

        $auth_arr = ['email' => request('email'), 'password' => request('password')];

        $input = $request->all();

        try {
            $oClient = $this->loginIMAPClient(
                $input["email"],
                $input["password"]
            );
        } catch(\Exception $e) {
            return response()->json(
                ['error'=>'Invalid email or password', "status" => 0],
                $this->_unAuthStatus
            );
        }

        $password_key = Crypt::encryptString($input['password']);

        if (User::where('email', request('email'))->exists()) {
            $user = User::where('email', $input["email"])->first();

            $user->key = $password_key;
            $user->password = bcrypt($input['password']);

            $user->save();
        } else {
            $input['key'] =  $password_key;
            $input['password'] = bcrypt($input['password']);

            $user = User::create($input);
        }

        $success['token'] = $user->createToken('Cmail')-> accessToken;
        $request->session()->put('auth_token', $success['token']);
        $request->session()->put('password_key', $password_key);
        Log::info(
            "Token successfully generated for the user",
            ['file' => __FILE__, 'line' => __LINE__]
        );
        return response()->json(
            ['success' => $success, "status" => 1],
            $this->_successStatus
        );
    }

    /**
     * Gets Current User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        Log::info(
            "User data retrieved successfully",
            ['file' => __FILE__, 'line' => __LINE__]
        );
        return response()->json(
            ['success' => $user, "status" => 1],
            $this->_successStatus
        );
    }

    /**
     * Logs out a user from the application
     *
     * @param Request $request Laravel Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info(
            "Logout function called",
            ['file' => __FILE__, 'line' => __LINE__]
        );
        if (Auth::check()) {
            Auth::user()->OauthAcessToken()->delete();
            $request->session()->flush();
        }
        return response()->json(
            [
                "status" => 1,
                "message" => "Logged out successfully"
            ],
            $this->_successStatus
        );
    }
}
