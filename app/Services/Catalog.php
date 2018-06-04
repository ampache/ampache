<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Classes\Catalog as Cat;
use Sse\Event;
use Sse\SSE;

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
    public static function catalog_worker($action, $catalogs = null, $options = null)
    {
        if (Config::get('interface.ajax_load')) {
            $sse_url = url("/SSE/catalog/" . $action . "/" . urlencode(json_encode($catalogs)));
        if ($options) {
            $sse_url .= "&options=" . urlencode(json_encode($_POST));
        }
        self::sse_worker($sse_url);
        } else {
        Cat::process_action($action, $catalogs, $options);
      }
    }

    public static function sse_worker($url)
    {
        echo '<script type="text/javascript">';
        echo "sse_worker('$url');";
        echo "</script>\n";
    }
    
}
