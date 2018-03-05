<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Local\Catalog_local;
class ModulesController extends Controller
{
  public function show_Catalogs()
  {
      $catalogs = array();
      $catalog_types = $this->get_module_types('catalog');
      foreach ($catalog_types as $type) {
          $class_name = '\\Modules\\Catalog\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;         
          $catalogs[] = new $class_name();
      }
      
      return view('modules.catalogs.index', compact('catalogs'));
      
      
  }
  
  public function action ($type, $action)
  {
      switch ($type)
      {
          case 'localplay':
            switch($action)
            {
                case 'index':
                    $modules = $this->get_module_types($type);
                    return view('modules.' . $type . '.index', compact('modules'));
                default:     
            }
            break;
          case 'catalog':
              switch ($action)
              {
                  case 'index':
                      $catalog_types = $this->get_module_types($type);
                      foreach ($catalog_types as $type) {
                          $class_name = '\\Modules\\Catalog\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
                          $modules[] = new $class_name();
                      }
                      return view('modules.' . $type . '.index', compact('modules'));
                  default:
              }
            break;
          case 'plugin':
              switch ($action)
              {
                  case 'index':
                      $modules = $this->get_module_types($type);
                      return view('modules.' . $type . '.index', compact('modules'));
                  default:
              }
      }
  }
  
  public function get_module_types($type)
  {
      $basedir = base_path('/modules/' . $type);
      $handle = opendir($basedir);
      if (!(file_exists($basedir))  || !($handle)) {
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
