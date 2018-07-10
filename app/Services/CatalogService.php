<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Classes\Catalog;
use App\Models\User;
use Sse\Event;
use Sse\SSE;
use Illuminate\Support\Facades\View;
use Modules\Catalogs\Local;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogService
{
    /**
     * Construct an instance of Catalog service.
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
   
    public function getCatTypes()
    {
        return $this->show_catalog_types();
    }
    /**
     * Show dropdown catalog types.
     * @param string $divback
     */
    public static function show_catalog_types($divback = 'catalog_type_fields')
    {
        $catTypes = "<script>\n" .
            "var type_fields = new Array();\n" .
            "type_fields['none'] = '';";
        $seltypes = '<option value="none">[Select]</option>';
        $types    = self::get_catalog_types();
        foreach ($types as $type) {
            $catalog = self::create_catalog_type($type);
            if ($catalog->is_installed()) {
                $seltypes .= '<option value="' . $type . '">' . $type . '</option>';
                $catTypes .= "type_fields['" . $type . "'] = \"";
                $fields = $catalog->catalog_fields();
                $help   = $catalog->get_create_help();
                if (!empty($help)) {
                    $catTypes .= "<tr><td></td><td>" . $help . "</td></tr>\n";
                }
                foreach ($fields as $key => $field) {
                    $catTypes .= "<tr><td style='width: 34%;'>" . $field['description'] . ":</td><td>";
                    
                    switch ($field['type']) {
                        case 'checkbox':
                            $catTypes .= "<div class='form-group'><input type='checkbox' name='" . $key . "' value='1' " . (($field['value']) ? 'checked' : '') . "/>";
                            break;
                        default:
                            $catTypes .= "<div class='form-group'><input type='" . $field['type'] . "'class=" . "w3-round" .
                                " name=" . $key . " value='" . (isset($field['value']) ? : '') . "'><div class='messages'></div></div>";
                            break;
                    }
                    $catTypes .= "</td></tr>";
                }
                $catTypes .= "\";";
            }
        }
        
        
        $catTypes .= "\nfunction catalogTypeChanged() {\n" .
            "var sel = document.getElementById('catalog_type');\n" .
            "var seltype = sel.options[sel.selectedIndex].value;\n" .
            "var ftbl = document.getElementById('" . $divback . "');\n" .
            "ftbl.innerHTML = '<table class=\"w3-table w3-small\">' + type_fields[seltype] + '</table>';\n" .
            "}\n</script>\n";
        
        return array($catTypes,$seltypes);
    }
    /**
     * get_catalog_types
     * This returns the catalog types that are activated
     * @return string[]
     */
    public static function get_catalog_types()
    {
        /* First open the dir */
        $basedir = base_path('modules/catalogs');
        $handle  = opendir($basedir);
        
        if (!is_resource($handle)) {
            //           debug_event('catalog', 'Error: Unable to read catalog types directory', '1');
            
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
            
            // Make sure the plugin base file exists inside the plugin directory
            if (! file_exists($basedir . '/' . $file . '/' . $file . '.catalog.php')) {
                //                debug_event('catalog', 'Missing class for ' . $file, 3);
                continue;
            }
            
            $results[] = $file;
        } // end while
        $tables = array();
        foreach ($results as $result) {
            if (Schema::hasTable('catalog_' . $result)) {
                $tables[] = $result;
            }
        }

        return $tables;
    } // get_catalog_types
    
    /**
     * create_catalog_type
     * This function attempts to create a catalog type
     * all Catalog modules should be located in /modules/catalog/<name>/<name>.class.php
     * @param string $type
     * @param int $id
     * @return Catalog|null
     */
    public static function create_catalog_type($type, $id=0)
    {
        if (!$type) {
            return false;
        }
        
        $filename = base_path('modules/catalogs/' . $type . '/' . $type . '.catalog.php');
        $include  = require_once $filename;
        
        if (!$include) {
            /* Throw Error Here */
            //            debug_event('catalog', 'Unable to load ' . $type . ' catalog type', '2');
            
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
                //                debug_event('catalog', $type . ' not an instance of Catalog abstract, unable to load', '1');
                
                return false;
            }
            
            return $catalog;
        }
    } // create_catalog_type
    
    public static function process_action($action, $catalogs, $options = null)
    {
        if (!$options || !is_array($options)) {
            $options = array();
        }
        
        switch ($action) {
            case 'add_to_all_catalogs':
                $catalogs = Catalog::get_catalogs();
            case 'add_to_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog($options);
                        }
                    }
                    
                    if (!defined('SSE_OUTPUT')) {
                        Log::error('catalog_add');
                    }
                }
                break;
            case 'update_all_catalogs':
                $catalogs = Catalog::get_catalogs();
            case 'update_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->verify_catalog();
                        }
                    }
                }
                break;
            case 'full_service':
                if (!$catalogs) {
                    $catalogs = Catalog::get_catalogs();
                }
                
                /* This runs the clean/verify/add in that order */
                foreach ($catalogs as $catalog_id) {
                    $catalog = Catalog::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        $catalog->clean_catalog();
                        $catalog->verify_catalog();
                        $catalog->add_to_catalog();
                    }
                }
                Dba::optimize_tables();
                break;
            case 'clean_all_catalogs':
                $catalogs = Catalog::get_catalogs();
            case 'clean_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->clean_catalog();
                        }
                    } // end foreach catalogs
                    Dba::optimize_tables();
                }
                break;
            case 'update_from':
                $catalog_id = 0;
                // First see if we need to do an add
                if ($options['add_path'] != '/' && strlen($options['add_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($options['add_path'])) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog(array('subdirectory' => $options['add_path']));
                        }
                    }
                } // end if add
                
                // Now check for an update
                if ($options['update_path'] != '/' && strlen($options['update_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($options['update_path'])) {
                        $songs = Song::get_from_path($options['update_path']);
                        foreach ($songs as $song_id) {
                            Catalog::update_single_item('song', $song_id);
                        }
                    }
                } // end if update
                
                if ($catalog_id <= 0) {
                    Log::error(__("This subdirectory is not part of an existing catalog. Update cannot be processed."));
                }
                break;
            case 'gather_media_art':
                if (!$catalogs) {
                    $catalogs = Catalog::get_catalogs();
                }
                
                // Iterate throught the catalogs and gather as needed
                foreach ($catalogs as $catalog_id) {
                    $catalog = Catalog::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        //                     require AmpConfig::get('prefix') . UI::find_template('show_gather_art.inc.php');
                        flush();
                        $catalog->gather_art();
                    }
                }
                break;
        }
        
        // Remove any orphaned artists/albums/etc.
        self::gc();
    }
}
