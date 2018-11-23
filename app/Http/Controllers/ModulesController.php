<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Local\Catalog_local;
use Modules\Catalog\Remote\Catalog_remote;
use Exception;
use Illuminate\Support\Facades\Schema;
use App\Models\Catalog;
use App\Services\PluginService;
use Illuminate\Http\Request;
use App\Services\LocalplayService;

class ModulesController extends Controller
{
    public function __construct()
    {
    }
    
    
    public function show_Catalogs()
    {
        $modules       = array();
        $module_type          = 'Catalog modules';
        
        $catalog_types = $this->get_module_types('catalog');
        foreach ($catalog_types as $type) {
            $class_name = 'Modules\\Catalog\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
            $modules[]  = new $class_name();
        }
        $title = 'Catalog';

        return view('modules.index', ['modules' => $modules, 'title' => $title, 'type' => $module_type]);
    }
 
    public function show_LocalPlay()
    {
        $title           = 'Localplay';
        $modules         = array();
        $module_type     = 'Localplay';
        $localplay_types = $this->get_module_types('localplay');
        foreach ($localplay_types as $type) {
            $class_name = 'Modules\\Localplay\\' . 'Ampache' . ucfirst($type)  ;
            $modules[]  = new $class_name();
        }
        $type = 'Localplay modules';
      
        return view('modules.index', ['modules' => $modules, 'title' => $title, 'type' => $module_type]);
    }
    
    public function show_plugins()
    {
        $plugins      = array();
        $title        = "Plugin";
        $module_type = 'Plugin';
        $plugin_types = PluginService::get_plugins();
        foreach ($plugin_types as $type) {
            $class_name = '\\Modules\\Plugin\\' . 'Ampache' . $type ;
            $plugins[]  = new $class_name();
            
        }
      
        return view('modules.index', ['modules' => $plugins, 'title' => $title, 'type' => $module_type]);
    }
  
  
    public function action($type, $action, request $request)
    {
        $module = $request->module;
        switch ($module)
        {
            case "Catalog":
                $module  = 'Catalog_' . $type;
                if (Schema::hasTable(lcfirst($module))) {
                    if ($action == 'Disable') {
                        DB::table('catalogs')->where('catalog_type', '=', $type)->delete();
                        Schema::dropIfExists(lcfirst($module));
                    }
                } else {
                    $catalog = new $module();
                    $catalog->install();
                }
                break;
            case "Plugin":
                $plugin = new PluginService();
                $plugin->_get_info($type); 
                if ($action == 'Enable') {
                    $plugin->install();
                } else {
                    $plugin->uninstall();
                }
                break;
                
            case "Localplay":
                $localplay = new LocalplayService();
                $localplay->_get_info($type);
                if ($action == 'Enable') {
                    $localplay->install();
                } else {
                    $localplay->uninstall();
                }
                break;
                
            default:
        }
        //Check to see if $type is installed
      
      
        return 'true';
    }
  
    public function get_module_types($type)
    {
        $basedir = base_path('modules/' . $type);
        try {
            $handle  = opendir($basedir);
        } catch (Exception $e) {
            Log::alert("Module directory doesn't exist or cannot be read");

            return array();
        }
    $results = array();
        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            /* Make sure it is a dir */
            if (! is_dir($basedir . '/' . $file)) {
                Log::alert('catalog: $preferencefile' . ' is not a directory.');
                continue;
            }
            switch ($type) {
                case 'localplay':
                    $fullPath = $basedir . '/' . $file . '/' . strtolower($file) . '.controller' . '.php';
                    break;
                case 'catalog':
                    $fullPath = $basedir . '/' . $file . '/' . strtolower($file) . '.' . $type . '.php';
                    break;
            }
            // Make sure the plugin base file exists inside the plugin directory
            if (! file_exists($fullPath)) {
                Log::alert('Missing class for ' . $file, [3]);
                continue;
            }
          
            $results[] = $file;
        } // end while
      
        return $results;
    }
    
    public function update(Request $request, $id)
    {
        $input         = $request->all();
        $catalog_types = $this->get_module_types('catalog');
        if (in_array($id, $catalog_types)) {
            if ($input[$id] == true) {
                $catalog = 'Catalog_' . $id;
                $cat_type == new $catalog();
                $cat_type->install();
            }
        }
        Schema::hasTable('catalog_' . $id);
        $preference = Catalog::find($id);
    }
}
