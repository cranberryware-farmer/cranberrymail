<?php
	/*ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);*/
    session_start();
    define('CM_VERSION','cmail_installer');
    define('SETTINGS','cmail_settings');

    function terminal($command)
    {
        //system
        if (function_exists('system')) {
            ob_start();
            system($command, $return_var);
            $output = ob_get_contents();
            ob_end_clean();
        }
        //passthru
        elseif (function_exists('passthru')) {
            ob_start();
            passthru($command, $return_var);
            $output = ob_get_contents();
            ob_end_clean();
        }

        //exec
        elseif (function_exists('exec')) {
            exec($command, $output, $return_var);
        }

        //shell_exec
        elseif (function_exists('shell_exec')) {
            $output = shell_exec($command) ;
        } else {
            $output = 'Command execution not possible on this system';
            $return_var = 1;
        }

        return $output;
    }

    function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last)
        {
            case 'g':
            $val *= 1024;
            case 'm':
            $val *= 1024;
            case 'k':
            $val *= 1024;
        }
        return $val;
    }
    function max_file_upload_in_bytes() {
        //select maximum upload size
        $max_upload = return_bytes(ini_get('upload_max_filesize'));
        //select post limit
        $max_post = return_bytes(ini_get('post_max_size'));
        //select memory limit
        $memory_limit = return_bytes(ini_get('memory_limit'));
        // return the smallest of them, this defines the real limit
        return min($max_upload, $max_post, $memory_limit);
    }
    function rrmdir($src) {
        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if ( is_dir($full) ) {
                    rrmdir($full);
                }
                else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
?>
<!doctype html>
<html>
    <head>
        <title>CranberryMail Setup</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
        <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
        <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css"/>
        <style type="text/css">
        </style>
    </head>
    <body>
        <?php $step = (isset($_REQUEST['step'])) ? $_REQUEST['step'] : null;?>
        <nav class="navbar navbar-light bg-light">
            <a class="navbar-brand" href="https://nettantra.com">
                <img src="https://cdn.nettantra.net/wp-content/uploads/2019/10/nettantra-logo-2019.svg" width="25%" height="25%" class="d-inline-block align-top" alt="Nettantra">
            </a>
        </nav>
        <?php if($step!=null) { ?>


        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item <?php if ($step==null) {
    echo "active";
}?>" aria-current="<?php if ($step==null) {
    echo "page";
}?>"><a href="?">Setup</a></li>
        <?php }

            if(isset($step)){

                if($step==1){ //php,apache check
                    $requirements = array(
                        array(
                            'classes' => array(
                                'ZipArchive' => 'zip',
                            ),
                            'functions' => array(
                                'token_name' => 'Tokenizer',
                                'mb_check_encoding' => 'Mbstring',
                                'openssl_cipher_iv_length' => 'Openssl',
                                'finfo_buffer' =>'Fileinfo',
                                'bcadd' => 'BCMath',
                                'ctype_alnum' => 'Ctype',
                                'json_encode' => 'JSON',
                                'curl_init' => 'Curl',
                                'simplexml_load_string' => 'SimpleXML',
                                'mysqli_init' => 'MySQLi',
                            ),
                            'defined' => array(
                                'PDO::ATTR_DRIVER_NAME' => 'PDO'
                            ),
                            'apache' => array(
                                'mod_rewrite' => 'mod_rewrite'
                            ),
                        )
                    );
                    function checkDependencies($requirements) {
                        $error = '';
                        $missingDependencies = array();

                        // do we have PHP 7.2.0 or newer?
                        if(version_compare(PHP_VERSION, '7.3.0', '<')) {
                            $error.='PHP 7.3.0 is required. Please ask your server administrator to update PHP to version 7.3.0 or higher.<br/>';
                        }

                        // running oC on windows is unsupported since 8.1
                        if(substr(PHP_OS, 0, 3) === "WIN") {
                            $error.='CranberryMail does not support Microsoft Windows.<br/>';
                        }

                        foreach ($requirements[0]['classes'] as $class => $module) {
                            if (!class_exists($class)) {
                                $missingDependencies[] = array($module);
                            }
                        }
                        foreach ($requirements[0]['functions'] as $function => $module) {
                            if (!function_exists($function)) {
                                $missingDependencies[] = array($module);
                            }
                        }
                        foreach ($requirements[0]['defined'] as $defined => $module) {
                            if (!defined($defined)) {
                                $missingDependencies[] = array($module);
                            }
                        }

                        if (function_exists('apache_get_modules')) {
                            foreach ($requirements[0]['apache'] as $defined => $module) {
                                if (!in_array($defined,apache_get_modules())) {
                                    $missingDependencies[] = array($module);
                                }
                            }
                        }
                        foreach ($requirements[0]['defined'] as $defined => $module) {
                            if (!defined($defined)) {
                                $missingDependencies[] = array($module);
                            }
                        }

                        // if (terminal('php -r \'echo "test";\'') !== 'test') {
                        //     $missingDependencies[] = array('PHP CLI');
                        // }

                        $uplaod_size = max_file_upload_in_bytes();
                        if($uplaod_size < 41943040) {
                            $missingDependencies[] = array("Increase upload_max_filesize, post_max_size, memory_limit from php.ini to atleast 40MB");
                        }

                        if(!empty($missingDependencies)) {
                            $error .= 'The following PHP modules are required to use CranberryMail:<br/>';
                        }
                        foreach($missingDependencies as $missingDependency) {
                            $error .= '<li>'.$missingDependency[0].'</li>';
                        }
                        if(!empty($missingDependencies)) {
                            $error .= '</ul><p style="text-align:center">Please contact your server administrator to install the missing modules.</p>';
                        }

                        // do we have write permission?
                        if(!is_writable('.')) {
                            $error.='Can\'t write to the current directory. Please fix this by giving the webserver user write access to the directory.<br/>';
                        }



                        return $error;
                    }
                    $result = checkDependencies($requirements);
                    ?>
                    <li class="breadcrumb-item active" aria-current="page">Step 1</li>
                </ol>
            </nav>
                    <div class="container">
                        <div class="card">
                            <h5 class="card-header">Checking PHP and Apache modules</h5>
                            <div class="card-body">
                                <h5 class="card-title">Results</h5>
                                <p class="card-text">
                                <?php if($result==""){
                                        echo "All dependencies have been met.";
                                    } else{
                                        echo "There are unmet dependencies.<br />".$result;

                                    } ?></p>
                                <?php if($result==""){ ?>
                                    <a href="?step=2" class="btn btn-primary">Next</a>
                                <?php }else{ ?>
                                    <a href="?step=1" class="btn btn-danger">Check Dependencies</a>
                               <?php } ?>
                            </div>
                        </div>
                    </div>
         <?php


                }elseif($step==2){ //upload or fetch from server
                    function isCertInfoAvailable() {
                        $curlDetails =  curl_version();
                        return version_compare($curlDetails['version'], '7.19.1')!= -1;
                    }

                    function getFile($url,$path) {
                        $error='';

                        $fp = fopen ($path, 'w+');
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                        if (isCertInfoAvailable()){
                            curl_setopt($ch, CURLOPT_CERTINFO, true);
                        }
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                        $data=curl_exec($ch);
                        $curlError=curl_error($ch);
                        curl_close($ch);
                        fclose($fp);

                        if($data==false){
                            $error.='Download of CranberryMail source file failed.<br />'.$curlError;
                        }
                        return($error.$curlError);

                    }
                    function install($directory) {
                        $error = '';
                        if($directory != "."){
                            if (!file_exists(__DIR__.'/'.$directory)) {
                                mkdir(__DIR__.'/'.$directory, 0755, true);
                            }
                        }

                        if($error=="" && file_exists('./'.$directory."/index.php"))
                        {
                            $error="u";
                        }

                        // downloading latest release
                        if (!file_exists('cmail.zip')) {
                            $error .= getFile('http://sh1.nettantra.in/releases/'.CM_VERSION.'.zip','cmail.zip');
                        }

                        // unpacking into nextcloud folder
                        $zip = new ZipArchive;
                        $res = $zip->open('cmail.zip');
                        if ($res==true) {
                            // Extract it to the tmp dir
                            $cm_tmp_dir = 'tmp-cm'.time();
                            $_SESSION['tmp_dir'] = $cm_tmp_dir;
                            $zip->extractTo($cm_tmp_dir);
                            $zip->close();

                            // Move it to the folder
                            if($error == "") {
                                foreach (array_diff(scandir($cm_tmp_dir.'/cranberrymail'), array('..', '.')) as $item) {
                                    rename($cm_tmp_dir.'/cranberrymail/'.$item, './' . $directory . '/' .$item);
                                }
                            } else if($error == "u") {
                                rename('./'.$directory. '/database', './'.$directory. '/database_old');
                                rename($cm_tmp_dir.'/cranberrymail/database', './'.$directory. '/database');
                            }
                        } else {
                            $error.='unzip of cranberrymail source file failed.<br />';
                        }

                        // deleting zip file
                        $result=@unlink('cmail.zip');

                        return($error);
                    }

                    $uploadFlag=false;
                    // print_r($_FILES);exit;
                    $download = 1;
                    if(isset($_FILES['cmail']) && $_FILES['cmail']["size"] > 0){
                        $ext = pathinfo($_FILES['cmail']["name"], PATHINFO_EXTENSION);
                        if($ext == "zip"){
                            $za = new ZipArchive;
                            $za->open($_FILES["cmail"]["tmp_name"]);
                            if($za->numFiles > 0 || $ext != "zip"){
                                $iresult=move_uploaded_file($_FILES["cmail"]["tmp_name"], "cmail.zip");
                            }
                        } else {
                            $download = 0;
                        }
                        $uploadFlag=true;
                    }


                    if(isset($_POST['imethod'])){
                        $_SESSION['dir'] = trim($_POST['directory']," ");

                        if($uploadFlag && $download == 0) {
                            $iresult = "Invalid Installation Zip File.<br />";
                        } else {
                            $iresult = install($_SESSION['dir']);

                            if(isset($_POST['app_url'])){
                                $path = getcwd();
                                if($_SESSION['dir']!='.'){
                                    $envPath=$path."/".$_SESSION['dir']."/".SETTINGS."/.env";
                                    $appUrl = $_POST['app_url']."/".$_SESSION['dir'];
                                } else {
                                    $envPath=$path."/".SETTINGS."/.env";
                                    $appUrl = $_POST['app_url'];
                                }
                                $data = [
                                    "app_name" => "Cmail",
                                    "env" => "local",
                                    "debug" => "",
                                    "log_level" => "error",
                                ];
                                if(!file_exists('./'.$_SESSION['dir']."/index.php")){
                                    $envFileData =
                                        'APP_NAME=\'' . $data['app_name']. "'\n" .
                                        'APP_ENV=' . $data['env'] . "\n" .
                                        'APP_KEY=' . 'base64:bODi8VtmENqnjklBmNJzQcTTSC8jNjBysfnjQN59btE=' . "\n" .
                                        'APP_DEBUG=false' . $data['debug'] . "\n" .
                                        'APP_LOG_LEVEL=' . $data['log_level']. "\n" .
                                        'APP_URL=' . $appUrl;
                                    if ($iresult!="u") {
                                        file_put_contents($envPath, $envFileData);
                                    }
                                }
                            }
                        }
                    } ?>
                    <li class="breadcrumb-item active" aria-current="page">Step 2</li>
                </ol>
            </nav>
                <div class="container">
                    <div class="card">
                    <h5 class="card-header">
			<?php if (isset($iresult) && !empty($iresult) && $iresult!='u') {
                        echo "Error";
                    } else {
                        echo "Installer Settings";
                    } ?> </h5>
                    <div class="card-body">
                    <form method="post" enctype="multipart/form-data" onsubmit="showProgress()">
                        <p class="card-text">
                           <?php if (isset($iresult) && !empty($iresult) && $iresult!='u') {
                        echo $iresult."</p>"; ?>
				<a href="?" class="btn btn-danger">Abort Installation</a>
                <a href="?step=rollback" class="btn btn-secondary">Rollback</a>
			 <?php  }elseif(isset($iresult) && !empty($iresult) && $iresult=='u'){ ?>
                    Please wait. Upgrading CranberryMail. </p>
            <?php } else {?>
                            <div class="form-group">
                                <label for="directory">Installation Directory/Folder</label>
                                <input type="text" name="directory" class="form-control" id="directory" placeholder="Enter installation directory/folder" aria-describedby="emailHelp">
                                <small id="emailHelp" class="form-text text-muted">Enter the name of directory or folder where CranberryMail will be installed. Use <strong>.</strong> for current directory or folder</small>
                            </div>

                            <fieldset class="form-group">
                                <div class="row">
                                <legend class="col-form-label col-sm-12 pt-0">Installation Method</legend>
                                </div>
                                <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-check">
                                    <input class="form-check-input" type="radio" name="imethod" id="exampleRadios1" value="server" checked>
                                    <label class="form-check-label" for="exampleRadios1">
                                    Server
                                    </label>
                                    </div>
                                    <div class="form-check">
                                    <input class="form-check-input" type="radio" name="imethod" id="exampleRadios2" value="upload">
                                    <label class="form-check-label" for="exampleRadios2">
                                        Upload zip file
                                    </label>
                                    </div>

                                </div>
                                </div>
                            </fieldset>

                            <br />
                            <p id="upload">

                                <input type="file" name="cmail" id="customFile" accept=".zip">
                                <br /><br />
                                <input type="hidden" name="app_url" id="lv_url" />
                                <input type="hidden" value="2" name="step" />
                                <input type="submit" class="btn btn-primary" value="Next" />
                            </p>

                        </p>
                        <input type="submit" class="btn btn-primary" id="proceed-3" value="Next" />
		<?php	} ?>
                        </form>

                    </div>
                </div>
                </div>
        <?php

                }elseif($step==3){ //folder permissions
                    $path = getcwd();
                    if(isset($_SESSION['dir']) && $_SESSION['dir']!='.'){
                        $start_dir = $path."/".$_SESSION['dir'];
                    }else{
                        $start_dir = $path;
                    }

                    function chmod_file_folder($dir) {
                        $perms=[];
                        $perms['file'] = 0644;
                        $perms['folder'] = 0755;

                        $dh=@opendir($dir);

                        if ($dh) {

                            while (false !==($file = readdir($dh))) {
                                if ($file != "." && $file != "..") {
                                    $fullpath = $dir .'/'. $file;
                                    if (!is_dir($fullpath)) {
                                        chmod($fullpath, $perms['file']);
                                    } else {
                                        chmod($fullpath, $perms['folder']);
                                        chmod_file_folder($fullpath);
                                    }
                                }
                            }
                            closedir($dh);
                        }
                    }

                    chmod_file_folder($start_dir);

                    function getPermission($folder)
                    {
                        $path = getcwd();
                        $dir = $path."/".$folder;

                        if(!is_dir($dir)){
                            mkdir($dir,0755,true);
                        }
                        return substr(sprintf('%o', fileperms($dir)),-4);

                    }

                    function isFolder755($perm){
                        if($perm=="755" || $perm=="777"){
                            return true;
                        }else{
                            return false;
                        }
                    }

                    $perm1 = getPermission($_SESSION['dir']."/storage/framework");
                    $perm2 = getPermission($_SESSION['dir']."/storage/logs");
                    $perm3 = getPermission($_SESSION['dir']."/bootstrap/cache");

                    $res1 = isFolder755($perm1);
                    $res2 = isFolder755($perm2);
                    $res3 = isFolder755($perm3);


                    if($res1 == false || $res2 == false || $res3 == false){
                        $status = "Errors Detected";
                        $result = false;
                    }else{
                        $status = "Success";
                        $result= true;
                    }

                    $sflag='<svg class="bi bi-check-circle-fill" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                  </svg>';

                    $eflag='<svg class="bi bi-x-circle-fill" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.146-3.146a.5.5 0 0 0-.708-.708L8 7.293 4.854 4.146a.5.5 0 1 0-.708.708L7.293 8l-3.147 3.146a.5.5 0 0 0 .708.708L8 8.707l3.146 3.147a.5.5 0 0 0 .708-.708L8.707 8l3.147-3.146z"/>
                  </svg>'; ?>
                    <li class="breadcrumb-item active" aria-current="page">Step 3</li>
            </ol>
        </nav>
                <div class="container">
                <div class="card">
                    <h5 class="card-header">Checking folder permissions</h5>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $status; ?></h5>
                        <p class="card-text">
                        <table class="table">
                            <thead class="thead-dark">
                                <tr>
                                <th scope="col">#</th>
                                <th scope="col">Folder</th>
                                <th scope="col">Permission</th>
                                <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                <th scope="row">1</th>
                                <td><?=$_SESSION['dir']?>/storage/framework/</td>
                                <td><?php echo $perm1; ?></td>
                                <td><?php echo ($res1) ? $sflag:$eflag; ?></td>
                                </tr>
                                <tr>
                                <th scope="row">2</th>
                                <td><?=$_SESSION['dir']?>/storage/logs/</td>
                                <td><?php echo $perm2; ?></td>
                                <td><?php echo ($res2) ? $sflag:$eflag; ?></td>
                                </tr>
                                <tr>
                                <th scope="row">3</th>
                                <td><?=$_SESSION['dir']?>/bootstrap/cache/</td>
                                <td><?php echo $perm3; ?></td>
                                <td><?php echo ($res3) ? $sflag:$eflag; ?></td>
                                </tr>
                            </tbody>
                            </table>
                        </p>
                        <?php
                            if($result){ ?>
                                <a href="?step=4&process=<?php echo $_GET['process']; ?>" class="btn btn-primary">Next</a>
                           <?php }else{ ?>
                            <a href="?step=3&process=<?php echo $_GET['process']; ?>" class="btn btn-danger">Check Permissions</a>
                          <?php } ?>

                    </div>
                </div>
                </div>
            <?php

                }elseif($step==4){ //set configuration

                    if ($_GET['process']=='u') {
                        $_ENV = array();
                        if ($_SESSION['dir']==".") {
                            $envPath = SETTINGS."/.env";
                        } else {
                            $envPath = $_SESSION['dir']."/".SETTINGS."/.env";
                        }
                        $handle = fopen($envPath, "r");
                        if ($handle) {
                            while (($line = fgets($handle)) !== false) {
                                if (strpos($line, "=") !== false) {
                                    $var = explode("=", $line);
                                    $_ENV[$var[0]] = trim($var[1]);
                                }
                            }
                            fclose($handle);
                        } else {
                            die('error opening .env');
                        }
                    } ?>
                    <li class="breadcrumb-item active" aria-current="page">Step 4</li>
            </ol>
        </nav>

            <div class="container">
                <div class="card">
                <h5 class="card-header">Configure CranberryMail</h5>
                <div class="card-body">
                <form id="env" method="post">
                <p class="card-text">

                    <div class="form-group">
							<label for="app_url">Application URL</label>
                            <input type="text" name="app_url" id="app_url" placeholder="Url of the application" class="form-control" required aria-describedby="appUrlHelpBlock" />
                            <small id="appUrlHelpBlock" class="form-text text-muted">Url where the application is going to be installed</small>
                    </div>
                    <div class="form-group">
							<label for="email_domain">Email</label>
                            <input type="email" name="email_domain" id="email_domain" placeholder="Your email" class="form-control" aria-describedby="emailHelpBlock" onblur="wizard()" />
                            <small id="emailHelpBlock" class="form-text text-muted">The domain of your email will be used to configure SMTP and IMAP settings</small>
                    </div>
					<fieldset>
						<legend>Database</legend>
						<div class="form-group">
							<label for="db">Database</label>
							<select name="database_connection" id="db"  class="custom-select custom-select-lg mb-3" aria-describedby="dbHelpBlock">
								<option value="mysql" <?php if(isset($_ENV['DB_CONNECTION']) && $_ENV['DB_CONNECTION']=="mysql" ) {echo "selected";}?>>MySQL</option>
								<option value="sqlite" <?php if(isset($_ENV['DB_CONNECTION']) && $_ENV['DB_CONNECTION']=="sqlite" ) { echo "selected"; }?>>SQLite</option>
								<option value="pgsql" <?php if(isset($_ENV['DB_CONNECTION']) && $_ENV['DB_CONNECTION']=="pgsql" ) { echo "selected"; }?>>Postgresql</option>
								<option value="sqlsrv" <?php if(isset($_ENV['DB_CONNECTION']) && $_ENV['DB_CONNECTION']=="sqlsrv" ){ echo "selected";}?>>SQLServer</option>
                            </select>
                            <small id="dbHelpBlock" class="form-text text-muted">Choose your database</small>
                </div>
						<div class="form-group">
							<label for="database_hostname">Hostname</label>
                            <input type="text" name="database_hostname" id="database_hostname" value="127.0.0.1" placeholder="Hostname" class="form-control" required value ="<?php if(isset($_ENV['DB_HOST'])) { echo $_ENV['DB_HOST']; }?>" />
                        </div>
						<div class="form-group">
							<label for="database_port">Port</label>
                    		<input type="number" name="database_port" id="database_port" class="form-control" value="3306" required value ="<?php if(isset($_ENV['DB_PORT'])) { echo $_ENV['DB_PORT']; }?>" />
                        </div>
						<div class="form-group">
							<label for="database_name">Name of Database</label>
							<input type="text" name="database_name" id="database_name"  placeholder="Database Name" class="form-control" required value ="<?php if(isset($_ENV['DB_DATABASE'])) { echo $_ENV['DB_DATABASE']; }?>" />
                        </div>
						<div class="form-group">
							<label for="database_username">DB Username</label>
							<input type="text" name="database_username" id="database_username" placeholder="Database Username" class="form-control" required value ="<?php if(isset($_ENV['DB_USERNAME'])) { echo $_ENV['DB_USERNAME']; }?>"/>
                        </div>
						<div class="form-group">
							<label for="database_password">DB Password</label>
							<input type="password" name="database_password" id="database_password" class="form-control" placeholder="Database Password" value ="<?php if(isset($_ENV['DB_PASSWORD'])) { echo $_ENV['DB_PASSWORD']; }?>" />
                        </div>
					</fieldset>
					<fieldset>
						<legend>IMAP Settings</legend>
						<div class="form-group">
							<label for="imap_host">Host</label>
							<input type="text" name="imap_host" id="imap_host" placeholder="IMAP Host" class="form-control" required value ="<?php if(isset($_ENV['IMAP_HOST'])) { echo $_ENV['IMAP_HOST']; }?>" />
                        </div>
						<div class="form-group">
							<label for="imap_port">Port</label>
                    		<input type="number" class="form-control" name="imap_port" id="imap_port" placeholder="IMAP Port" required value ="<?php if(isset($_ENV['IMAP_PORT'])) { echo $_ENV['IMAP_PORT']; }?>" />
                        </div>
						<div class="form-group">
							<label for="imap_encryption">Encryption</label>
							<select name="imap_encryption" id="imap_encryption"  class="custom-select custom-select-lg mb-3">
								<option value="ssl" <?php if(isset($_ENV['IMAP_ENCRYPTION']) && $_ENV['IMAP_ENCRYPTION']=="ssl" ) {echo "selected";}?>>SSL</option>
								<option value="starttls" <?php if(isset($_ENV['IMAP_ENCRYPTION']) && $_ENV['IMAP_ENCRYPTION']=="starttls" ) {echo "selected";}?>>STARTTLS</option>
								<option value="tls" <?php if(isset($_ENV['IMAP_ENCRYPTION']) && $_ENV['IMAP_ENCRYPTION']=="tls" ) {echo "selected";}?>>TLS</option>
							</select>
                        </div>
					</fieldset>
					<fieldset>
						<legend>SMTP Settings</legend>
						<div class="form-group">
							<label for="smtp_host">Host</label>
							<input type="text" name="smtp_host" id="smtp_host" placeholder="SMTP Host" class="form-control" required value ="<?php if(isset($_ENV['SMTP_HOST'])) { echo $_ENV['SMTP_HOST']; }?>"/>
                        </div>
						<div class="form-group">
							<label for="smtp_port">Port</label>
                    		<input type="number" name="smtp_port" class="form-control" id="smtp_port" placeholder="SMTP Port" required value ="<?php if(isset($_ENV['SMTP_PORT'])) { echo $_ENV['SMTP_PORT']; }?>" />
                        </div>
						<div class="form-group">
							<label for="smtp_encryption">Encryption</label>
							<select name="smtp_encryption" id="smtp_encryption"  class="custom-select custom-select-lg mb-3">
								<option value="ssl" selected <?php if(isset($_ENV['SMTP_ENCRYPTION']) && $_ENV['SMTP_ENCRYPTION']=="ssl" ) {echo "selected";}?>>SSL</option>
								<option value="starttls" <?php if(isset($_ENV['SMTP_ENCRYPTION']) && $_ENV['SMTP_ENCRYPTION']=="starttls" ) {echo "selected";}?>>STARTTLS</option>
								<option value="tls" <?php if(isset($_ENV['SMTP_ENCRYPTION']) && $_ENV['SMTP_ENCRYPTION']=="tls" ) {echo "selected";}?>>TLS</option>
							</select>
                        </div>
					</fieldset>
                    <input type="hidden" name="app_name" id="app_name" value="Cmail"  />
					<input type="hidden" name="environment" id="environment" value="production" />
					<input type="hidden" name="app_debug" id="app_debug_false" value=false />
					<input type="hidden" name="app_log_level" id="app_log_level" value="error" />
					<input type="hidden" name="broadcast_driver" id="broadcast_driver" value="log"  />
					<input type="hidden" name="cache_driver" id="cache_driver" value="file" />
					<input type="hidden" name="session_driver" id="session_driver" value="file" />
					<input type="hidden" name="queue_driver" id="queue_driver" value="sync" />
					<input type="hidden" name="redis_hostname" id="redis_hostname" value="127.0.0.1" />
					<input type="hidden" name="redis_password" id="redis_password" value="null" />
					<input type="hidden" name="redis_port" id="redis_port" value="6379" />
					<input type="hidden" name="mail_driver" id="mail_driver" value="smtp" />
					<input type="hidden" name="mail_host" id="mail_host" value="smtp.mailtrap.io" />
				    <input type="hidden" name="mail_port" id="mail_port" value="2525" />
					<input type="hidden" name="mail_username" id="mail_username" value="null" />
					<input type="hidden" name="mail_password" id="mail_password" value="null" />
					<input type="hidden" name="mail_encryption" id="mail_encryption" value="null" />
				    <input type="hidden" name="pusher_app_id" id="pusher_app_id" value=""  />
					<input type="hidden" name="pusher_app_key" id="pusher_app_key" value="" />
					<input type="hidden" name="pusher_app_secret" id="pusher_app_secret" value=""  />
					<input type="hidden" name="step" value="5" />
					<input type="hidden" name="update_stat" value="<?php echo $_GET['process']=='u' ? 'u' : 'i';?>" />


                    </p>
                    <input type="submit" class="btn btn-primary" value="Next" />
                    </form>
                </div>
                </div>
            </div>


         <?php

                } else if ($step=="rollback") {
                    $path = getcwd();
                    $target_dir = $path . "/" . $_SESSION['dir'];
                    $src_tmp_dir = $path . "/" . $_SESSION['tmp_dir'];
                    if(is_dir($target_dir.'/database_old')){
                        rename($target_dir.'/database_old', $target_dir."/database");
                    }
                    if(file_exists($src_tmp_dir)){
                        rrmdir($src_tmp_dir);
                    }
                    header('Location: /');
                }else{ //dB migration and end
                    $path = getcwd();
                    if(isset($_SESSION['dir']) && $_SESSION['dir']!='.'){
                        $envPath=$path."/".$_SESSION['dir']."/".SETTINGS."/.env";
                    }else{
                        $envPath=$path."/".SETTINGS."/.env";
                    }

                    $error = 1;
                    $cmd = "cd ".$path;

                    if(isset($_SESSION['dir']) && $_SESSION['dir']!='.'){
                        $cmd = $cmd."/".$_SESSION['dir'];
                    }

                    if($_POST['database_connection'] == "mysql"){
                        $conn = new mysqli($_POST['database_hostname'], $_POST['database_username'], $_POST['database_password'], $_POST['database_name'], $_POST['database_port']);
                        if($conn->connect_errno > 0) {
                            $error = 2;
                        }

                        $query = mysqli_query($conn,"CREATE TABLE IF NOT EXISTS CREATE_ACCESS_CHECK (
                            id INT NOT NULL
                        )");

                        if(!$query) {
                            $error = 2;
                        } else {
                            $query1 = mysqli_query($conn,"DROP TABLE CREATE_ACCESS_CHECK");
                            $error = 0;
                        }
                    } else {
                        $error = 0;
                    }
                    $path = getcwd();
                    $target_dir = $path . "/" . $_SESSION['dir'];
                    $src_tmp_dir = $path . "/" . $_SESSION['tmp_dir'];
                    if($error == 0) {
                        if($_POST['update_stat'] == "i") {
                            $envFileData =
                                'APP_NAME=\'' . $_POST['app_name'] . "'\n" .
                                'APP_ENV=' . $_POST['environment'] . "\n" .
                                'APP_KEY=' . 'base64:bODi8VtmENqnjklBmNJzQcTTSC8jNjBysfnjQN59btE=' . "\n" .
                                'APP_DEBUG=' . $_POST['app_debug'] . "\n" .
                                'APP_LOG_FILE=\'cmail_settings/app.log\''."\n".
                                'APP_LOG_LEVEL=' . $_POST['app_log_level'] . "\n" .
                                'APP_URL=' . $_POST['app_url'] . "\n\n" .
                                'DB_CONNECTION=' . $_POST['database_connection'] . "\n" .
                                'DB_HOST=' . $_POST['database_hostname'] . "\n" .
                                'DB_PORT=' . $_POST['database_port'] . "\n" .
                                'DB_DATABASE=' . $_POST['database_name'] . "\n" .
                                'DB_USERNAME=' . $_POST['database_username'] . "\n" .
                                'DB_PASSWORD=' . $_POST['database_password'] . "\n\n" .
                                'IMAP_HOST='.$_POST['imap_host']."\n".
                                'IMAP_PORT='.$_POST['imap_port']."\n".
                                'IMAP_ENCRYPTION='.$_POST['imap_encryption']."\n\n".
                                'SMTP_HOST='.$_POST['smtp_host']."\n".
                                'SMTP_PORT='.$_POST['smtp_port']."\n".
                                'SMTP_ENCRYPTION='.$_POST['smtp_encryption']."\n\n".
                                'BROADCAST_DRIVER=' . $_POST['broadcast_driver'] . "\n" .
                                'CACHE_DRIVER=' . $_POST['cache_driver'] . "\n" .
                                'SESSION_DRIVER=' . $_POST['session_driver'] . "\n" .
                                'QUEUE_DRIVER=' . $_POST['queue_driver'] . "\n\n" .
                                'REDIS_HOST=' . $_POST['redis_hostname'] . "\n" .
                                'REDIS_PASSWORD=' . $_POST['redis_password'] . "\n" .
                                'REDIS_PORT=' . $_POST['redis_port'] . "\n\n" .
                                'MAIL_DRIVER=' . $_POST['mail_driver'] . "\n" .
                                'MAIL_HOST=' . $_POST['mail_host'] . "\n" .
                                'MAIL_PORT=' . $_POST['mail_port'] . "\n" .
                                'MAIL_USERNAME=' . $_POST['mail_username'] . "\n" .
                                'MAIL_PASSWORD=' . $_POST['mail_password'] . "\n" .
                                'MAIL_ENCRYPTION=' . $_POST['mail_encryption'] . "\n\n" .
                                'PUSHER_APP_ID=' . $_POST['pusher_app_id'] . "\n" .
                                'PUSHER_APP_KEY=' . $_POST['pusher_app_key'] . "\n" .
                                'PUSHER_APP_SECRET=' . $_POST['pusher_app_secret'];

                            file_put_contents($envPath, $envFileData);
                            //Copy full folders

                            foreach (array_diff(scandir($src_tmp_dir.'/cranberrymail'), array('','..', '.', 'database', 'cmail_settings')) as $item) {
                                rename($src_tmp_dir.'/cranberrymail/'.$item, $target_dir."/".$item);
                            }
                        } else {
                            //Update and preg_replace line
                            $lines = file($envPath);
                            $result = '';

                            foreach($lines as $line) {
                                if(strpos($line, 'APP_URL=') === 0) {
                                    $result .= 'APP_URL=' . $_POST['app_url'] ."\n";
                                } else if (strpos($line, 'DB_CONNECTION=') === 0) {
                                    $result .= 'DB_CONNECTION=' . $_POST['database_connection'] ."\n";
                                } else if (strpos($line, 'DB_HOST=') === 0) {
                                    $result .= 'DB_HOST=' . $_POST['database_hostname'] ."\n";
                                } else if (strpos($line, 'DB_PORT=') === 0) {
                                    $result .= 'DB_PORT=' . $_POST['database_port'] ."\n";
                                } else if (strpos($line, 'DB_DATABASE=') === 0) {
                                    $result .= 'DB_DATABASE=' . $_POST['database_name'] ."\n";
                                } else if (strpos($line, 'DB_USERNAME=') === 0) {
                                    $result .= 'DB_USERNAME=' . $_POST['database_username'] ."\n";
                                } else if (strpos($line, 'DB_PASSWORD=') === 0) {
                                    $result .= 'DB_PASSWORD=' . $_POST['database_password'] ."\n";
                                } else if (strpos($line, 'IMAP_HOST=') === 0) {
                                    $result .= 'IMAP_HOST='.$_POST['imap_host'] ."\n";
                                } else if (strpos($line, 'IMAP_PORT=') === 0) {
                                    $result .= 'IMAP_PORT='.$_POST['imap_port'] ."\n";
                                } else if (strpos($line, 'IMAP_ENCRYPTION=') === 0) {
                                    $result .= 'IMAP_ENCRYPTION='.$_POST['imap_encryption'] ."\n";
                                } else if (strpos($line, 'SMTP_HOST=') === 0) {
                                    $result .= 'SMTP_HOST='.$_POST['smtp_host'] ."\n";
                                } else if (strpos($line, 'SMTP_PORT=') === 0) {
                                    $result .= 'SMTP_PORT='.$_POST['smtp_port'] ."\n";
                                } else if (strpos($line, 'SMTP_ENCRYPTION=') === 0) {
                                    $result .= 'SMTP_ENCRYPTION='.$_POST['smtp_encryption'] ."\n";
                                } else {
                                    $result .= $line;
                                }
                            }

                            file_put_contents($envPath, $result);

                            //Copy other folders
                            foreach (array_diff(scandir($src_tmp_dir.'/cranberrymail'), array('..', '.', 'database', 'storage', 'cmail_settings')) as $item) {
                                rrmdir($target_dir."/" . $item);
                                rename($src_tmp_dir.'/cranberrymail/'.$item, $target_dir."/" .$item);
                            }
                        }

                        
                        if(is_dir($src_tmp_dir)) {
                            rrmdir($src_tmp_dir);
                        }
                        if(is_dir($target_dir."/database_old")) {
                            rrmdir($target_dir."/database_old");
                        }
                    }
                    if (terminal('php -r \'echo "test";\'') === 'test') {
                        $cmd = $cmd." && php artisan migrate && php artisan db:seed";
                        terminal($cmd);
                    } else {
                        $error = 1;
                    } ?>
                    <li class="breadcrumb-item active" aria-current="page">Finish</li>
            </ol>
        </nav>
        <div class="container">
                <div class="card">
                <h5 class="card-header">Finish Installation</h5>

                <div class="card-body">
                    <p class="card-text"><?php if ($error == 2) {?>
                        User Does not have create privilage to the Database. Please make sure that the database user has right privilages. And run the following commands in your installation directory/folder.
                        <ol>
                            <li>php artisan migrate</li>
                            <li>php artisan db:seed</li>
                        </ol>
                    <?php } else {
                        echo "CranberryMail has been installed successfully.";
                    } ?></p>
                    <a href="javascript:void(0)" id="cran_migrate" class="btn btn-primary">Migrate</a>
                    <a href="/<?=$_SESSION['dir']?>" id="cran_finish" class="btn btn-primary" style="display: none;">Finish</a>
                    <?php if ($error != 0){?>
                    <a href="?step=rollback" id="cran_rollback" class="btn btn-secondary">Rollback</a>
                    <?php } ?>
                </div>
                </div>
            </div>
        <?php

                }

            }else{
                ?></ol>
                <div class="jumbotron jumbotron-fluid">
                    <div class="container">
                        <h1 class="display-4">CranberryMail</h1>
                        <p class="lead">Welcome to CranberryMail Setup.</p>
                        <hr class="my-4">
                        <p>Let us help you setup CranberryMail on your server</p>
                        <p class="lead">
                            <a class="btn btn-primary btn-lg" href="?step=1" role="button">Start</a>
                        </p>
                    </div>
                </div>
                <?php
            }?>

<script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-loading-overlay/2.1.7/loadingoverlay.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>

        <script>
            $(document).ready(function(){
                /*Start of code for step2*/
                $("#upload").hide();
                $("#exampleRadios2").click(function(){
                    $("#upload").show();
                    $("#proceed-3").hide();
                });
                $("#exampleRadios1").click(function(){
                    $("#upload").hide();
                    $("#proceed-3").show();
                });
                /*End of code for step2*/
            });



        function isEmail(email) {
            let regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }

        if(document.getElementById("lv_url") !=null){
            let path = window.location.pathname;
            let arr = path.split("/");
            let segment = arr.pop();

            while(segment!="setupv3.php"){
                segment=arr.pop();
            }
            let url = window.location.origin+arr.join("/");
            url = url.trim();
            document.getElementById("lv_url").value=url;

        }

        if(document.getElementById("app_url") !=null){
            let path = window.location.pathname;
            let arr = path.split("/");
            let segment = arr.pop();

            while(segment!="setupv3.php"){
                segment=arr.pop();
            }

            let dir = "<?php echo isset($_SESSION['dir']) ? $_SESSION['dir']: ''; ?> ";
            dir=dir.trim();
            if(dir!='.'){
                arr.push(dir);
            }
            let url = window.location.origin+arr.join("/");
            url = url.trim();
            document.getElementById("app_url").value=url;
            window._api = url+'/api/v1';

        }

        function showProgress(){
            jQuery.LoadingOverlay("show", {
                                text: "Installation is in progress",
                                textAnimation: "fadein"
                            });
        }

        function db_check(){
                jQuery.LoadingOverlay("show",{
                                text: "Checking for existing database",
                                textAnimation: "fadein"
                            });
                let conn = jQuery("#db").val();
                let hostname = jQuery("#database_hostname").val();
                let db = jQuery("#database_name").val();
                let username =jQuery("#database_username").val();
                let password = jQuery("#database_password").val();

                jQuery.post(_api+"/db_check",{
                       conn: conn,
                       hostname: hostname,
                       db: db,
                       username: username,
                       password: password,

                },function(data){
                        jQuery.LoadingOverlay("hide");

                        if(data.status!=1){
                            alertify.confirm("CranberryMail",data.message,
                            function(){
                                jQuery.LoadingOverlay("show",{
                                    text: "Creating new database",
                                    textAnimation: "fadein"
                                 });
                                jQuery.post(_api+"/drop_create_db",{
                                    conn: conn,
                                    hostname: hostname,
                                    db: db,
                                    username: username,
                                    password: password,

                                },function(data){
                                    jQuery.LoadingOverlay("hide");

                                    if(data.status!=1){
                                        alertify.alert("CranberryMail",data.message);
                                    }else{
                                        jQuery("#env").submit();
                                    }

                                }).fail(function(){
                                    jQuery.LoadingOverlay("hide");
                                    alertify.alert("CranberryMail","Please delete and create the database manually");
                                });
                            },
                            function(){
                                alertify.alert("CranberryMail","Please change database name or installer will use the existing database");
                            });
                        }else{
                            jQuery("#env").submit();
                        }

                }).fail(function(){
                    jQuery.LoadingOverlay("hide");
                    alertify.alert("CranberryMail","Unable to detect database. Please create the database manually and proceed.");
                });
        }

        function wizard(){
            let email=jQuery("#email_domain").val();

            if(isEmail(email)){
                jQuery.LoadingOverlay("show",{
                                text: "Fetching SMTP and IMAP values",
                                textAnimation: "fadein"
                            });
                jQuery.post(_api+"/wizard/emailsettings",{
                        email: email
                },function(data){
                        jQuery.LoadingOverlay("hide");


                        if(data.status==1){
                            jQuery("#imap_host").val(data.imap.host);
                            jQuery("#imap_port").val(data.imap.port);
                            jQuery("#imap_encryption").val(data.imap.encryption);

                            jQuery("#smtp_host").val(data.smtp.host);
                            jQuery("#smtp_port").val(data.smtp.port);
                            jQuery("#smtp_encryption").val(data.smtp.encryption);
                        }else{
                            alertify.alert("CranberryMail",data.msg);
                        }

                }).fail(function(){
                    jQuery.LoadingOverlay("hide");
                    alertify.alert("CranberryMail","Unable to detect mail server settings. Please update the settings below manually.");
                });
            }else{
                alertify.alert("CranberryMail","Please enter a valid email address");
            }
        }

        jQuery("#env").one("submit",function(e){
            e.preventDefault();
            db_check();
        });

        jQuery("#cran_migrate").click(function(){
            let url = window.location.origin+"/<?php if(in_array($_SESSION['dir'], ['', '.'])){echo '';} else {echo $_SESSION['dir'];}?>";
            url = url.trim();
            let cr_api = url+'/api/v1';
            jQuery.LoadingOverlay("show",{
                text: "Completing Migrations",
                textAnimation: "fadein"
            });
            jQuery.post(cr_api+"/wizard/migrate",{},
            function(data){
                jQuery.LoadingOverlay("hide");

                if(data.success){
                    jQuery("#cran_migrate").hide();
                    jQuery("#cran_rollback").hide();
                    jQuery("#cran_finish").show();
                } else {
                    jQuery("#cran_finish").hide();
                    jQuery.LoadingOverlay("hide");
                    alertify.alert("CranberryMail","Please run Migrate script manually");
                }

            }).fail(function(){
                jQuery("#cran_finish").hide();
                jQuery.LoadingOverlay("hide");
                alertify.alert("CranberryMail","Please run Migrate script manually");
            });
        });



   <?php if(isset($_POST['imethod']) && $iresult== '') { ?>
                            jQuery.LoadingOverlay("show",{
                                text: "CranberryMail installation is in progress",
                                textAnimation: "fadein"
                            });

                            setTimeout(function(){
                                window.location=window.location.origin+window.location.pathname+"?step=3&process=i";
                            }, 3000);


                    <?php
                    }else{
                        if(isset($_POST['imethod']) && $iresult== 'u') { ?>
                            jQuery.LoadingOverlay("show",{
                                text: "CranberryMail upgrade is in progress",
                                textAnimation: "fadein"
                            });
                            setTimeout(function(){
                                window.location=window.location.origin+window.location.pathname+"?step=3&process=u";
                            }, 3000);


                  <?php  }
                  }
			?>
        </script>

    </body>
</html>
