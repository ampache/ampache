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

}
