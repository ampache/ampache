<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use App\Models\Catalog;
use Classes\UI;
use Modules\Catalogs\Local\Catalog_local;
use Illuminate\Support\Facades\DB;
use Modules\Catalogs\Remote\Catalog_remote;
use Modules\Catalogs\Subsonic\Catalog_subsonic;
use Modules\Catalogs\Beetsremote\Catalog_beetsremote;
use Modules\Catalogs\Seafile\Catalog_seafile;

class ModuleController extends Controller
{
    public function __construct()
    {
        UI::flip_class(['odd','even']);
    }
    
    public function show_catalog_modules()
    {
        $tables = DB::select("SHOW TABLES LIKE 'catalog\_%'");
        $catalog_types = $this->get_catalog_types();
        foreach ($catalog_types as $type) {
            $class_name = '\\Modules\\Catalogs\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
            
            $catalogs[] = new $class_name();
            
        }
        
         return view('includes.catalog_modules', compact('catalogs'));
    }
    
    public function show_plugins()
    {
        
    }
    
    public function install_catalog_types()
    {
     }
    
    public function uninstall_catalog_type()
    {
        $catalogs = $this->get_catalog_types();
        return view('include.catalog_types', compact('catalogs'));
        
    }
    
    public function get_catalog_types()
    {
        /* First open the dir */
        $basedir = base_path('/modules/Catalogs');
        $handle  = opendir($basedir);
        
        if (!is_resource($handle)) {
//            debug_event('catalog', 'Error: Unable to read catalog types directory', '1');
            
            return array();
        }
        
        $results = array();
        
        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            /* Make sure it is a dir */
            if (! is_dir($basedir . '/' . $file)) {
//                debug_event('catalog', $file . ' is not a directory.', 3);
                continue;
            }
            $temp = $basedir . '/' . $file . '/' . $file . '.catalog.php';
            // Make sure the plugin base file exists inside the plugin directory
            if (! file_exists($basedir . '/' . $file . '/' . strtolower($file) . '.catalog.php')) {
//                debug_event('catalog', 'Missing class for ' . $file, 3);
                continue;
            }
            
            $results[] = $file;
        } // end while
        
        return $results;
    } // get_catalog_types

    public function catalog_action($type, $action)
    {
        
    }
    
}
