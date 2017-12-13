<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\Preference;
use App\Models\User;

class InstallController extends Controller
{
    public $database;
    
    public function selectLanguage()
    {
        $languages = array_keys(config('languages'));
        
        return view('install.language', compact('languages'));
    }
    
    public function setLanguage($language)
    {
         $this->writeConfig('locale', 'app',$language);
        //return redirect('install.check');
        return redirect('/install/system_check');
    }
    
    public function create_db(Request $request)
    {
        $new_user       = $request->input('db_username', false);
        $new_pass       = $request->input('db_pass', false);
        $overwrite      = $request->input('overwrite_db', false);
        $admin_username = e(trim($request->input('admin_username', 'root')));
        $admin_password = e($request->input('admin_pass'));
        $hostname       = e($request->input('local_host', 'localhost'));
        $this->database = e($request->input('local_db'));
        $port           = $request->local_port != null ? : 3306;
        $skip_admin     = isset($_POST['skip_admin']) ? True : false;
        $create_db      = $request->input('create_db', False);
        $create_tables  = $request->input('create_tables', false);
        $create_db_user = $request->input('create_db_user', False);
        
        //First test connection with authorized username/password
        $conn;
        $dsn = 'mysql:host=' . $hostname;
        try {
            $conn = new \PDO($dsn, $admin_username,$admin_password);
        } catch (\PDOException $e) {
            $this->getPDOMessage($e->getCode());
            return back();
        }
        
        //Check for existing schema
        $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $this->database . "';";
        $rows = $conn->query($sql);
        if ($skip_admin == false) {
            if ($create_db == "true") {
                if (($rows) && ($overwrite == "false")) {
                    $request->session()->flash('status', T_("Create database set without Overwrite"));
                    return back();
                }
                else if ($rows){
                    $sql = "DROP DATABASE `" . $this->database . "`;";
                    $conn->exec($sql);
                }
                $sql = "CREATE DATABASE `$this->database` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
                $conn->exec($sql);
            }
        }
        if ($create_tables == "true") {
            //Artisan needs to get info from configuration.
            config(['database.connections.mysql.database' => $this->database]);
            config(['database.connections.mysql.host' => $hostname]);
            config(['database.connections.mysql.username' => $admin_username]);
            config(['database.connections.mysql.password' => $admin_password]);
            $exitCode = Artisan::call('migrate');
            $exitCode = Artisan::call('db:seed');
            //Write database info to environmental file ,env
            $env = array('DB_HOST'=>$hostname, 'DB_PORT' =>$port, 'DB_DATABASE'=>$this->database, 'DB_USERNAME'=>$admin_username,
                'DB_PASSWORD'=>$admin_password);
            $this->updateEnv($env);
        }
        
        // Check to see if we should create a user here
        if ($create_db_user == "true") {
            $sql = "CREATE USER `" . $new_user . "`@`" . $hostname . "` IDENTIFIED BY '" . $new_pass . "'";

           $rows = $conn->exec($sql);
            
            $info = $conn->errorInfo();
            if (!is_null($info[2])) {
                $this->request->session()->flash('status', T_("Unable to add database user"));
                return '/install/show_db';
            }
            $sql = "GRANT ALL PRIVILEGES ON ampache.* TO '" . $new_user . "'@'" . $hostname . "' WITH GRANT OPTION";
            $rows = $conn->exec($sql);
            $env = array('DB_USERNAME'=>$new_user, 'DB_PASSWORD' => $new_pass);
            $this->updateEnv($env);
             
        } // end if we are creating a user
        
        return '/install/show_config';        
    }
    
    public function create_config(Request $request)
    { 
        $backends = $request->input('backends');
        $transcode_template = $request->input('transcode_temlate');
        $preferences = new Preference();
        if ($backends) {
            foreach ($backends as $backend) {
                $module = $backend . '_backend';
                $preferences->$module = True;
                $preferences->save();
            }
        }
        $this->writeConfig('transcode_cmd', 'transcoding', $request->input('transcode_template'));
        return '/install/show_account';
        
    }
  
    public function show_config()
    {
        $lang     = config('app.locale');
        $charset  = config('database.connections.mysql.charset');
        $modes    = $this->install_get_transcode_modes();
        $apache   = $this->install_check_server_apache();
        return view('install.configure', compact( 'lang', 'charset', 'modes', 'apache'));
    }
    
    public function create_account(Request $request)
    {
        $username = $request->input('local_username');
        $password = $request->input('local_pass');
        
        $count = User::where('username', $username)->count();
        if ($count > 0) {
            $request->session()->flash('Error', T_('User name already exists!'));
            return 'back';
            
        }
        $user = User::create(['username' => $username,'password' => Hash::make($password)]);
        
        $user->access = 100;
        $user->save();
        return 'next';
    }
    public function show_account()
    {
        return view('install.account');
        
    }
    private function updateEnv($env_vars)
    {
        $envFile = base_path('.env');
        $envStr  =file_get_contents($envFile);
        $keys = array_keys($env_vars);
        
        foreach ($keys as $key) {
            switch ($key)  {
                case 'DB_DATABASE':
                    $success = preg_match("~(?m)^DB_DATABASE=([\_\-\w]+)$~", $envStr,  $olddb);
                    $envStr=str_replace("DB_DATABASE=" . $olddb[1], "DB_DATABASE=" .$env_vars['DB_DATABASE'], $envStr);
                    break;
                case 'DB_HOST':
                    $success = preg_match("~(?m)^DB_HOST=(\w+)$~", $envStr, $oldhost );
                    $envStr=str_replace("DB_HOST=" . $oldhost[1], "DB_HOST=" . $env_vars['DB_HOST'], $envStr);
                    break;
                case 'DB_PORT':
                    $success = preg_match("~(?m)^DB_PORT=(\w+)$~", $envStr, $oldport);
                    $envStr=str_replace("DB_PORT=" . $oldport[1], "DB_PORT=" . $env_vars['DB_PORT'], $envStr);
                    break;
                case 'DB_USERNAME':
                    $success = preg_match("~(?m)^DB_USERNAME=(\w+)$~", $envStr, $olduser);
                    $envStr=str_replace("DB_USERNAME=" . $olduser[1], "DB_USERNAME=" . $env_vars['DB_USERNAME'], $envStr);
                    break;
                case 'DB_PASSWORD':
                    $success = preg_match("~(?m)^DB_PASSWORD=(\w+)$~", $envStr, $oldpassword);
                    $envStr=str_replace("DB_PASSWORD=" . $oldpassword[1], "DB_PASSWORD=" . $env_vars['DB_PASSWORD'], $envStr);
                    break;
                case 'APP_INSTALLED':
                    $success = preg_match("~(?m)^APP_INSTALLED=(\w+)$~", $envStr, $oldpassword);
                    $envStr=str_replace("APP_INSTALLED=" . $oldpassword[1], "APP_INSTALLED=" . $env_vars['APP_INSTALLED'], $envStr);
                    break;
                default:                    
            }
        }
        file_put_contents(base_path('.env'), $envStr);
    }

    private function getPDOMessage($errorNo)
    {
        switch ($errorNo) {
            case 1007:
                $message = "Can't create database '" . $this->database . "'; database exists and overwrite is not enabled.";
            case 1045:
                $message = "Administrative username or password incorrect.";
                break;
            case 1049:
                $message = "Unknown Database";
                break;
            case 2002:
                $message = "Hostname/IP incorrect.";
                break;
            case 1130:
                $message = "Host '" . gethostname() . "' is not allowed to connect to this MySQL server.";
            default:
                $message = "Unknown PDO error.";
        }
        $this->request->session()->flash('status', T_($message));
    }
    

    private function command_exists($command)
    {
        if (!function_exists('proc_open')) {
            return false;
        }
    
        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
        $process        = proc_open(
            "$whereIsCommand $command",
            array(
                0 => array("pipe", "r"), //STDIN
                1 => array("pipe", "w"), //STDOUT
                2 => array("pipe", "w"), //STDERR
            ),
            $pipes
            );
    
        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
    
            return $stdout != '';
        }
    
        return false;
    }
    
    /**
     * install_get_transcode_modes
     * get transcode modes available on this machine.
     */
    private function install_get_transcode_modes()
    {
        $modes = array();
    
        if ($this->command_exists('ffmpeg')) {
            $modes[] = 'ffmpeg';
        }
        if ($this->command_exists('avconv')) {
            $modes[] = 'avconv';
        }
        
        return $modes;
    } // install_get_transcode_modes
    
    public function install_check_server_apache()
    {
        return (strpos($_SERVER['SERVER_SOFTWARE'], "Apache/") === 0);
    }
    
    private function writeConfig($paramName, $configName, $newValue) {
        $configFile = config_path($configName . '.php');
        $configStr  = file_get_contents($configFile);
        switch ($configName)
        {
            case 'transcoding':
                $success = preg_match("~(?<=\'" . $paramName . "\')\s*\=\>\s*[\'\"](\w*)[\'\"]~", $configStr,  $oldconfig);
                $new_configStr = str_ireplace($oldconfig[1], $newValue, $configStr);
                break;
            default:
                $success = preg_match("~(?<=\'" . $paramName . "\')\s*\=\>\s*\'*(\w*)\'*~", $configStr,  $oldconfig);
                $new_configStr=preg_replace("~" . $oldconfig[1] . "~", $newValue , $configStr);
        }
        file_put_contents($configFile, $new_configStr);
        
    }
}
