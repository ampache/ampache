<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\Preference;
use App\Models\User;

class InstallController extends Controller
{
    protected $request;
    protected $database;
    
    public function install () {
        
    }
    
    public function selectLanguage()
    {
        $languages = array_keys(config('languages'));
        
    }
    
    public function setLanguage(Request $request)
    {
        $this->writeConfig('locale', 'app', $language);
        
        //return redirect('install.check');        
//        return response()->view('/install/system_check'>cookie('install_phase', 'requirements', 0));
    }
    
    public function create_db(Request $request)
    {
        $this->request  = $request;
        $new_user       = '';
        $new_pass       = '';
        $overwrite      = $request->input('overwrite_db', false);
        $username       = e(trim($request->input('local_username', 'root')));
        $password       = e($request->input('local_pass'));
        $hostname       = e($request->input('local_host', 'localhost'));
        $database       = e($request->input('local_db'));
        $port           = $request->local_port != null ? : 3306;
        $skip_admin     = isset($_POST['skip_admin']) ? true : false;
        $create_db      = $request->input('create_db', true);
        $create_tables  = $request->input('create_tables', false);
        
        if (isset($_POST['db_user']) && ($_POST['db_user'] == 'create_db_user')) {
            $new_user = $_POST['db_username'];
            $new_pass = $_POST['db_password'];
            
            if (!strlen($new_user) || !strlen($new_pass)) {
                $request->session()->flash('status', T_('Error: Ampache SQL Username or Password missing'));

                return back();
            }
        }
        //Tests connection with given database name and authorized username/password
        $conn;
        $dsn = 'mysql:host=' . $hostname;
        try {
            $conn = new \PDO($dsn, $username, $password);
        } catch (\PDOException $e) {
            $this->getPDOMessage($e->getCode());

            return back();
        }
            
        if (!$skip_admin) {
            if ($this->install_insert_db($conn, $database, $create_db, $overwrite, $create_tables) == false) {
                return back();
            }
            //Create the tables.
            //Artisan needs to get info from configuration.
            config(['database.connections.mysql.database' => $database]);
            config(['database.connections.mysql.host' => $hostname]);
            config(['database.connections.mysql.username' => $username]);
            config(['database.connections.mysql.password' => $password]);
            $exitCode = Artisan::call('migrate');
            $exitCode = Artisan::call('db:seed');
        }

        // Check to see if we should create a user here
        if (strlen($new_user) && strlen($new_pass)) {
            $sql     = 'GRANT ALL PRIVILEGES ON `' . $database . '`.* TO ' .
                "'" . $new_user . "'";
            if ($hostname == 'localhost' || strpos(hostname, '/') === 0) {
                $sql .= "@'localhost'";
            }
            $sql .= " IDENTIFIED BY '" . $new_pass . "' WITH GRANT OPTION";
        
            $rows = $conn->exec($sql);
            $info = $conn->errorInfo();
            if (!is_null($info[2])) {
                $this->request->session()->flash('status', T_("Unable to add database user"));

                return back();
            }
            $admin_name     = $new_user;
            $admin_password = $new_pass;
        } else {
            $admin_name     = $username;
            $admin_password = $password;
        }
        // end if we are creating a user
        //Write database info to environmental file ,env
        $env = array('DB_HOST' => $hostname, 'DB_PORT' => $port, 'DB_DATABASE' => $database, 'DB_USERNAME' => $admin_name, 'DB_PASSWORD' => $admin_password);
        $this->updateEnv($env);
        
        $lang     = config('app.locale');
        $charset  = config('database.connections.mysql.charset');
        $modes    = $this->install_get_transcode_modes();
        $apache   = $this->install_check_server_apache();
        
        return view('install.configure', compact('admin_name', 'admin_password', 'hostname', 'database', 'port', 'lang', 'charset', 'modes', 'apache'));
    }
    
    public function create_config(Request $request)
    {
        $backends           = $request->input('backends');
        $transcode_template = $request->input('transcode_temlate');
        $preferences        = new Preference();
        if ($backends) {
            foreach ($backends as $backend) {
                $module               = $backend . '_backend';
                $preferences->$module = true;
                $preferences->save();
            }
        }
        $this->writeConfig('transcode_cmd', 'transcoding', $request->input('transcode_template'));

        return view('install.account');
    }
  
    public function create_account(Request $request)
    {
        $username  = $request->input('local_username');
        $password  = $request->input('local_pass');
        $password2 = $request->input('local_pass2');
        if (strcmp($password, $password2) != 0) {
            $request->session()->flash('Error', T_('Passwords don\'t match!'));

            return view('install.account');
        }
        
        $count = User::where('username', $username)->count();
        if ($count > 0) {
            $request->session()->flash('Error', T_('User name already exists!'));

            return view('install.account');
        }
        $user = User::create(['username' => $username,'password' => Hash::make($password)]);
        
        $user->access = 100;
        $user->save();
        $env = array('APP_INSTALLED' => "True");
        $this->updateEnv($env);

        return view('pages.index');
    }
    
    
    public function install_insert_db($conn, $database, $create_db = true, $overwrite = false, $create_tables = true)
    {
        // Make sure that the database name is valid
        $t = preg_match('/([^\d\w\_\-[0-9])/', $database, $matches);
        
        if (count($matches)) {
            $this->request->session()->flash('status', T_('Error: Invalid database name.'));

            return false;
        }
       
        //Test for existing database and create if not existing.
        
        $result = Schema::hasTable('mytable');
        
        $sql = "CREATE DATABASE `$database` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
        $conn->query($sql);
        $t = $conn->errorInfo();
            
        if (($t[1] == 1007) && ($overwrite == true)) {
            $sql = "DROP DATABASE `" . $database . "`;";
            $conn->query($sql);
        } elseif (intval($t[1]) > 0) {
            $this->getPDOMessage($t[1]);

            return false;
        }
        $sql = "CREATE DATABASE `$database` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
        $conn->query($sql);

        return true;
    }
    
    private function updateEnv($env_vars)
    {
        $envFile = base_path('.env');
        $envStr  =file_get_contents($envFile);
        $keys    = array_keys($env_vars);
        
        foreach ($keys as $key) {
            switch ($key) {
                case 'DB_DATABASE':
                    $success = preg_match("~(?m)^DB_DATABASE=([\_\-\w]+)$~", $envStr, $olddb);
                    $envStr  =str_replace("DB_DATABASE=" . $olddb[1], "DB_DATABASE=" . $env_vars['DB_DATABASE'], $envStr);
                    break;
                case 'DB_HOST':
                    $success = preg_match("~(?m)^DB_HOST=(\w+)$~", $envStr, $oldhost);
                    $envStr  =str_replace("DB_HOST=" . $oldhost[1], "DB_HOST=" . $env_vars['DB_HOST'], $envStr);
                    break;
                case 'DB_PORT':
                    $success = preg_match("~(?m)^DB_PORT=(\w+)$~", $envStr, $oldport);
                    $envStr  =str_replace("DB_PORT=" . $oldport[1], "DB_PORT=" . $env_vars['DB_PORT'], $envStr);
                    break;
                case 'DB_USERNAME':
                    $success = preg_match("~(?m)^DB_USERNAME=(\w+)$~", $envStr, $olduser);
                    $envStr  =str_replace("DB_USERNAME=" . $olduser[1], "DB_USERNAME=" . $env_vars['DB_USERNAME'], $envStr);
                    break;
                case 'DB_PASSWORD':
                    $success = preg_match("~(?m)^DB_PASSWORD=(\w+)$~", $envStr, $oldpassword);
                    $envStr  =str_replace("DB_PASSWORD=" . $oldpassword[1], "DB_PASSWORD=" . $env_vars['DB_PASSWORD'], $envStr);
                    break;
                case 'APP_INSTALLED':
                    $success = preg_match("~(?m)^APP_INSTALLED=(\w+)$~", $envStr, $oldpassword);
                    $envStr  =str_replace("APP_INSTALLED=" . $oldpassword[1], "APP_INSTALLED=" . $env_vars['APP_INSTALLED'], $envStr);
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
                // no break
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
                // no break
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
    
    private function writeConfig($paramName, $configName, $newValue)
    {
        $configFile = config_path($configName . '.php');
        $configStr  = file_get_contents($configFile);
        switch ($configName) {
            case 'transcoding':
                $success       = preg_match("~(?<=\'" . $paramName . "\')\s*\=\>\s*[\'\"](\w*)[\'\"]~", $configStr, $oldconfig);
                $new_configStr = str_ireplace($oldconfig[1], $newValue, $configStr);
                break;
            default:
                $success      = preg_match("~(?<=\'" . $paramName . "\')\s*\=\>\s*\'*(\w*)\'*~", $configStr, $oldconfig);
                $new_configStr=preg_replace("~" . $oldconfig[1] . "~", $newValue, $configStr);
        }
        file_put_contents($configFile, $new_configStr);
    }
}
