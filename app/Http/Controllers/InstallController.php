<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstallController extends Controller
{
    public function selectLanguage()
    {
        $languages = array_keys(config('languages'));
        
        return view('install.language', compact('languages'));
    
    }
    
    public function setLanguage($language)
    {
        $oldStr = "'locale' => '" . config('app.locale') . "'";
        $newStr = "'locale' => '" . $language . "'";
        $appFile = config_path('app.php');
        $appStr=file_get_contents($appFile);
        $str=str_replace("$oldStr", "$newStr",$appStr);
        config(['app.locale' => $language]);
        file_put_contents($appFile, $str);
        //return redirect('install.check');
        return redirect('/install/system_check');
        
    }
    
    public function create_db(Request $request)
    {
        // Clean up incoming variables
        $username   = scrub_in($_REQUEST['local_username']);
        $password   = $_REQUEST['local_pass'];
        $hostname   = scrub_in($_REQUEST['local_host']);
        $database   = scrub_in($_REQUEST['local_db']);
        $port       = scrub_in($_REQUEST['local_port']);
        $skip_admin = isset($_REQUEST['skip_admin']);
        
            if (isset($_POST['db_user']) && ($_POST['db_user'] == 'create_db_user')) {
                $new_user = $_POST['db_username'];
                $new_pass = $_POST['db_password'];
            
            
                if (!strlen($new_user) || !strlen($new_pass)) {
                    $request->session()->flash('status', T_('Error: Ampache SQL Username or Password missing'));
                    return back();
                }
        
                if (!strlen($new_user) || !strlen($new_pass)) {
                    return back();
                }
            }
        if (!$skip_admin) {
            $success = $this->install_insert_db($new_user, $new_pass, $_REQUEST['create_db'], $_REQUEST['overwrite_db'], $_REQUEST['create_tables']);
        }
    }
    
    
    public function install_insert_db($db_user = null, $db_pass = null, $create_db = true, $overwrite = false, $create_tables = true)
    {
    }

}
