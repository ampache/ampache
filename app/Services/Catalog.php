<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Catalog
{
    /**
     * Construct an instance of Playlist service.
     *
     */
    public function __construct()
    {
    }
    
    public function getCatalogModules()
    {
        $basedir = base_path('modules/catalog');
        $results = array();
        $files   = File::directories($basedir);
        foreach ($files as $file) {
            /* Make sure it is a dir */
            if (!File::isDirectory($file)) {
                Log::warning($file . ' is not a directory.');
                continue;
            }
            
            $filename = pathinfo($file, PATHINFO_FILENAME);
            // Make sure the plugin base file exists inside the plugin directory
            if (!File::exists($file . '/' . $filename . '.catalog.php')) {
                Log::warning('Missing class for ' . $filename);
                continue;
            }
            
            $results[] = $filename;
        }

        return $results;
    }
    
    /**
     * create_catalog_type
     * This function attempts to create a catalog type
     * all Catalog modules should be located in /modules/catalog/<name>/<name>.class.php
     * @param string $type
     * @param int $id
     * @return Catalog|null
     */
    public function create_catalog_type($type, $id=0)
    {
        if (!$type) {
            return false;
        }
        
        $filename = base_path( '/modules/catalog/' . $type . '/' . $type . '.catalog.php');
        $include  = require_once $filename;
        
        if (!$include) {
            /* Throw Error Here */
            Log::debug('catalog', 'Unable to load ' . $type . ' catalog type');
            
            return false;
        } // include
        else {
            $class_name = "Catalog_" . $type;
            if ($id > 0) {
                $catalog = new $class_name($id);
            } else {
                $catalog = new $class_name();
            }
            if (!($catalog instanceof Catalog)) {
                Log::debug('catalog', $type . ' not an instance of Catalog abstract, unable to load');
                
                return false;
            }
            
            return $catalog;
        }
    } // create_catalog_type
    
}
