<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use SebastianBergmann\Environment\Runtime;

class InstallController extends Controller
{
    protected $request;
    protected $database;
    
    public function selectLanguage()
    {
        $languages = array_keys(config('languages'));
        
        return view('install.language', compact('languages'));
    }
    
    public function setLanguage($language)
    {
        $oldStr  = "'locale' => '" . config('app.locale') . "'";
        $newStr  = "'locale' => '" . $language . "'";
        $appFile = config_path('app.php');
        $appStr  = file_get_contents($appFile);
        $str     = str_replace("$oldStr", "$newStr", $appStr);
        config(['app.locale' => $language]);
        file_put_contents($appFile, $str);
        //return redirect('install.check');
        return redirect('/install/system_check');
    }
    
    public function create_db(Request $request)
    {
        $this->request = $request;
        $new_user      = '';
        $new_pass      = '';
        $overwrite     = $request->input('overwrite_db', false);
        $username   = e(trim($request->input('local_username', 'root')));
        $password   = e($request->input('local_pass'));
        $hostname   = e($request->input('local_host', 'localhost'));
        $database   = e($request->input('local_db'));
        $port       = $request->local_port != null ? : 3306;
        $skip_admin = $request->input('skip_admin', false);
        $create_db  = $request->input('create_db', true);
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
        $dsn = 'mysql:host=' . $hostname . ';' . 'dbname=' . $database;
        try {
            $conn = new \PDO($dsn, $username, $password);
        } catch (\PDOException $e) {
            $this->getPDOMessage($e->getCode());

            return false;
        }
            
        if (!$skip_admin) {
            if ($this->install_insert_db($conn, $database, $create_db, $overwrite, $create_tables) == false) {
                return back();
            }
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

                return false;
            }
            $admin_name = $new_user;
            $admin_password = $new_pass;
        }
        else {
            $admin_name = $username;
            $admin_password = $password;
        }
            // end if we are creating a user
        //Write database info to environmental file ,env
        $this->updateEnv($hostname, $port, $database, $admin_name, $admin_password);
        //Create the tables.
        //Artisan needs to get info from configuration.
        config(['database.connections.mysql.database' => $database]);
        config(['database.connections.mysql.host' => $hostname]);
        config(['database.connections.mysql.username' => $username]);
        config(['database.connections.mysql.password' => $password]);
        $exitCode = Artisan::call('migrate');
        $lang = config('app.locale');
        $charset = config('database.connections.mysql.charset');
        $modes = $this->install_get_transcode_modes();
        $apache = $this->install_check_server_apache();
        
        return view('install.configure', compact('admin_name', 'admin_password', 'hostname', 'database', 'port', 'lang', 'charset', 'modes', 'apache'));
    }
    
    public function create_config()
    {
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
        
        $sql = "CREATE DATABASE `$database` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
        $conn->query($sql);
        $t = $conn->errorInfo();
            
        if (($t[1] == 1007) && ($overwrite == true)) {
            $sql = "DROP DATABASE `" . $database . "`;";
            $conn->query($sql);
        } else {
            $this->getPDOMessage($t);

            return false;
        }
        $sql = "CREATE DATABASE `$database` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
        $conn->query($sql);

        return true;
    }
    
    private function updateEnv($db_host, $db_port, $db_database, $admin_name, $admin_password)
    {
        $envFile = base_path('.env.example');
        $envStr  =file_get_contents($envFile);
        $success = preg_match("~(?m)^DB_DATABASE=([\_\-\w]+)$~", $envStr, $database);
        $success = preg_match("~(?m)^DB_PORT=(\w+)$~", $envStr, $port);
        $success = preg_match("~(?m)^DB_HOST=(\w+)$~", $envStr, $host);
        $success = preg_match("~(?m)^DB_USERNAME=(\w+)$~", $envStr, $username);
        $success = preg_match("~(?m)^DB_PASSWORD=(\w+)$~", $envStr, $password);
        
        $envStr=str_replace("DB_DATABASE=" . $database[1], "DB_DATABASE=" . $db_database, $envStr);
        $envStr=str_replace("DB_PORT=" . $port[1], "DB_PORT=" . $db_port, $envStr);
        $envStr=str_replace("DB_HOST=" . $host[1], "DB_HOST=" . $db_host, $envStr);
        $envStr=str_replace("DB_USERNAME=" . $username[1], "DB_USERNAME=" . $admin_name, $envStr);
        $envStr=str_replace("DB_PASSWORD=" . $password[1], "DB_PASSWORD=" . $admin_password, $envStr);
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
    
}
