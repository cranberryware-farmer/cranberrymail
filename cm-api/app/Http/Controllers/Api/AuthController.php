<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use App\User; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Validator;

class AuthController extends Controller 
{
    public $successStatus = 200;
    
    public function login(Request $request){ 
        Log::info("Entered login function",['file' => __FILE__, 'line' => __LINE__]);
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('Cmail')-> accessToken; 
            Log::info("Token generated for user",['file' => __FILE__, 'line' => __LINE__]);
            return response()->json(['success' => $success, "status" => 1], $this-> successStatus); 
        } else{
            if (User::where('email', request('email'))->exists()) {
                Log::info("User is not authorised",['file' => __FILE__, 'line' => __LINE__]);
                return response()->json(['error'=>'Unauthorised', "status" => 0], 401);    
            }
            
            $validator = Validator::make($request->all(), 
                    [ 
                    'email' => 'required|email',
                    'password' => 'required'
            ]);   
         
            if ($validator->fails()) {
                Log::error("Error while validating your email or password",['file' => __FILE__, 'line' => __LINE__]);          
                return response()->json(['error'=>$validator->errors(), 'status' => 0], 401);
            }
                
           

            $input = $request->all();
            
            

            try {
                $oClient = new \Horde_Imap_Client_Socket([
                    'username' => $input["email"],
                    'password' => $input["password"],
                    'hostspec' => env('IMAP_HOST'),
                    'port' => env('IMAP_PORT'),
                    'secure' => env("IMAP_ENCRYPTION") //ssl,tls etc
                ]);
                Log::info("Authenticating with IMAP server", ['file' => __FILE__, 'line' => __LINE__]);
                $oClient->login();
              
            }
            catch(\Exception $e) {
                return response()->json(['error'=>'Invalid email or password', "status" => 0], 401);
            }

            $input['key'] =  Crypt::encryptString($input['password']);
            $input['password'] = bcrypt($input['password']);
            
            $user = User::create($input); 
            
            $success['token'] =  $user->createToken('AppName')-> accessToken;
            Log::info("Token successfully generated for the user",['file' => __FILE__, 'line' => __LINE__]); 
            return response()->json(['success' => $success, "status" => 1], $this-> successStatus);
            
        } 
    }
    
    public function getUser() {
        $user = Auth::user();
        Log::info("User data retrieved successfully",['file' => __FILE__, 'line' => __LINE__]);
        return response()->json(['success' => $user, "status" => 1], $this->successStatus); 
    }

    public function logout(){
        Log::info("Logout function called",['file' => __FILE__, 'line' => __LINE__]);
        if (Auth::check()) {
            Auth::user()->OauthAcessToken()->delete();
        }
        return response()->json([
            "status" => 1,
            "message" => "Logged out successfully" 
        ], 200);
    }
} 
