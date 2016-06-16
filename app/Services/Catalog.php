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
        $files = File::directories($basedir);
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
}