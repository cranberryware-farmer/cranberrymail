<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
            Log::info("Moving to login screen",['file' => __FILE__, 'line' => __LINE__]);
            return view('reactidx');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function db_check(Request $request){
        $conn = $_POST["conn"];
        $servername = $_POST['hostname'];
        $db = $_POST['db'];
        $username = $_POST["username"];
        $password = $_POST["password"];
        $flag = 0;

        try {
            $pdo = new \PDO("$conn:host=$servername;dbname=$db", $username, $password);
            // set the PDO error mode to exception
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return response()->json([
                "status" => 0,
                "message" => "Please click on OK to drop existing database. Click on Cancel to choose new database or use existing database."
            ], 200);
        } catch(\PDOException $e) {
            $flag = 1;
        }

        if($flag==1){
            try {
                $pdo = new \PDO("$conn:host=".$servername, $username, $password);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $db = "`".str_replace("`","``",$db)."`";
                $pdo->query("CREATE DATABASE IF NOT EXISTS $db");
                $pdo->query("use $db");
                return response()->json([
                    "status" => 1,
                    "message" => "DB created"
                ], 200);
            } catch(\PDOException $e) {
                return response()->json([
                    "status" => 0,
                    "message" => "No DB found"
                ], 200);
            }


        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drop_create_db(Request $request){

        $conn = $_POST["conn"];
        $servername = $_POST['hostname'];
        $db = $_POST['db'];
        $username = $_POST["username"];
        $password = $_POST["password"];

        try{
            $pdo = new \PDO("$conn:host=$servername", $username, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $db = "`".str_replace("`","``",$db)."`";
            $pdo->query("DROP DATABASE $db");
            $pdo->query("CREATE DATABASE IF NOT EXISTS $db");
            $pdo->query("use $db");
            return response()->json([
                "status" => 1,
                "message" => "New database has been created"
            ], 200);
        } catch(\PDOException $e) {
            return response()->json([
                "status" => 0,
                "message" => "Unable to delete database. Please delete and create new database manually to proceed."
            ], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ChangeSessionDriver(Request $request) {
        $this->controlSessionDriver("file", "local");
        return response()->json([
            "status" => 1,
            "message" => "Session Driver is set to file."
        ], 200);
    }
}
