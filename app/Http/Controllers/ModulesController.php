<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Catalogs\Local\Catalog_local;
use Modules\Catalogs\Remote\Catalog_remote;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Http\Middleware\Authenticate;

class ModulesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'clearance'])->except('index', 'show');
    }
    
    
    public function show_Catalogs()
    {
        $catalogs      = array();
        $catalog_types = $this->get_module_types('catalog');
        foreach ($catalog_types as $type) {
            $class_name = '\\Modules\\Catalogs\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
            $modules[]  = new $class_name();
        }
      
        return view('modules.catalog.index', compact('modules'));
    }
 
    public function show_LocalPlay()
    {
        $catalogs      = array();
        $catalog_types = $this->get_module_types('catalog');
        foreach ($catalog_types as $type) {
            $class_name = '\\Modules\\Catalog\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
            $catalogs[] = new $class_name();
        }
      
        return view('modules.catalogs.index', compact('catalogs'));
    }
    public function show_Plugins()
    {
        $catalogs      = array();
        $catalog_types = $this->get_module_types('catalog');
        foreach ($catalog_types as $type) {
            $class_name = '\\Modules\\Catalog\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
            $catalogs[] = new $class_name();
        }
      
        return view('modules.catalogs.index', compact('catalogs'));
    }
  
  
    public function action($type, $action)
    {
        $module  = 'Catalog_' . $type;
        $catalog = new $module();
      //Check to see if $type is installed
      
      if (Schema::hasTable('catalog_' . $type)) {
          if ($action == 'Disable') {
              Schema::drop('catalog_' . $type);
          }
      } else {
          $catalog->install();
      }
      
        return 'true';
    }
  
    public function get_module_types($type)
    {
        $basedir = base_path('/modules/' . str_plural($type));
        $handle  = opendir($basedir);
        if (!(file_exists($basedir)) || !($handle)) {
            Log::alert("Modules directory doesn't exist or cannot be read");

            return array();
        }
        $results = array();
      
        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
          /* Make sure it is a dir */
          if (! is_dir($basedir . '/' . $file)) {
              Log::alert('catalog: $file' . ' is not a directory.');
              continue;
          }
            $temp = $basedir . '/' . $file . '/' . $file . '.catalog.php';
          // Make sure the plugin base file exists inside the plugin directory
          if (! file_exists($basedir . '/' . $file . '/' . strtolower($file) . '.catalog.php')) {
              Log::alert('Missing class for ' . $file, 3);
              continue;
          }
          
            $results[] = $file;
        } // end while
      
      return $results;
    }
}
