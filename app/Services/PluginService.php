<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Update_Info;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PluginService
{
    /* Base Variables */
    public $name;

    /* constructed objects */
    public $_plugin;

    /**
     * Constructor
     * This constructor loads the Plugin config file which defines how to
     * install/uninstall the plugin from Ampache's database
     */
    public function __construct($name = null)
    {
//       Load the plugin */
        if ($name != null) {
            if (!$this->_get_info($name)) {
                return false;
            }
        }

        return true;
    } // Constructor
   
    /**
     * _get_info
     * This actually loads the config file for the plugin the name of the
     * class contained within the config file must be Plugin[NAME OF FILE]
     */
    public function _get_info($cname)
    {
        try {
            $basedir = base_path('/modules/plugin');
            if (is_dir($basedir . '/' . $cname)) {
                $name = $cname;
            } else {
                $name = 'ampache-' . strtolower($cname);
            }
            
            /* Require the file we want */
            if (!@include_once($basedir . '/' . $name . '/' . $cname . '.plugin.php')) {
                Log::error('Cannot include plugin `' . $cname . '`.');

                return false;
            }
            $this->name = $name;

            $plugin_name   = "\\Modules\\plugin\\Ampache$cname";
            $this->_plugin = new $plugin_name();

            if (!$this->is_valid()) {
                return false;
            }
        } catch (Exception $ex) {
            Log::error('Error when initializing plugin `' . $cname . '`: ' . $ex->getMessage());

            return false;
        }

        return true;
    } // _get_info

    /**
     * get_plugins
     * This returns an array of plugin names
     */
    public static function get_plugins($type='')
    {
        // make static cache for optimization when multiple call
        static $plugins_list = array();
        if (isset($plugins_list[$type])) {
            return $plugins_list[$type];
        }

        $plugins_list[$type] = array();

        // Open up the plugin dir
        $basedir = base_path('/modules/plugin');
        $handle  = opendir($basedir);

        if (!is_resource($handle)) {
            debug_event('Plugins', 'Unable to read plugins directory', '1');
        }

        // Recurse the directory
        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            // Take care of directories only
            if (!is_dir($basedir . '/' . $file)) {
                debug_event($file . ' is not a directory.');
                continue;
            }
            
            // If directory name start with ampache-, this is an external plugin and some parsing is required
            if (strpos($file, "ampache-") === 0) {
                $cfile = ucfirst(substr($file, 8));
            } else {
                $cfile = $file;
            }
            
            // Make sure the plugin base file exists inside the plugin directory
            if (! file_exists($basedir . '/' . $file . '/' . $cfile . '.plugin.php')) {
                Log::error('Missing class for ' . $cfile);
                continue;
            }
            
            if ($type != '') {
                $plugin = new PluginService($cfile);
                if (! PluginService::is_installed($plugin->_plugin->name)) {
                    Log::error('Plugin ' . $plugin->_plugin->name . ' is not installed, skipping');
                    continue;
                }
                if (! $plugin->is_valid()) {
                    Log::error('Plugin ' . $cfile . ' is not valid, skipping');
                    continue;
                }
                if (! method_exists($plugin->_plugin, $type)) {
                    Log::error('Plugin ' . $cfile . ' does not support ' . $type . ', skipping');
                    continue;
                }
            }
            // It's a plugin record it
            $plugins_list[$type][$cfile] = $cfile;
        } // end while

        // Little stupid but hey
        ksort($plugins_list[$type]);

        return $plugins_list[$type];
    } // get_plugins

    /**
     * is_valid
     * This checks to make sure the plugin has the required functions and
     * settings. Ampache requires public variables name, description, and
     * version (as an int), and methods install, uninstall, and load. We
     * also check that Ampache's database version falls within the min/max
     * version specified by the plugin.
     */
    public function is_valid()
    {
        /* Check the plugin to make sure it's got the needed vars */
        if (!strlen($this->_plugin->name)) {
            return false;
        }
        if (!strlen($this->_plugin->description)) {
            return false;
        }
        if (!strlen($this->_plugin->version)) {
            return false;
        }

        /* Make sure we've got the required methods */
        if (!method_exists($this->_plugin, 'install')) {
            return false;
        }

        if (!method_exists($this->_plugin, 'uninstall')) {
            return false;
        }

        if (!method_exists($this->_plugin, 'load')) {
            return false;
        }

        // We've passed all of the tests
        return true;
    } // is_valid

    /**
     * is_installed
     * This checks to see if the specified plugin is currently installed in
     * the database, it doesn't check the files for integrity
     */
    public static function is_installed($plugin_name)
    {
        /* All we do is check the version */
        return self::get_plugin_version($plugin_name);
    } // is_installed

    /**
     * install
     * This runs the install function of the plugin and inserts a row into
     * the update_info table to indicate that it's installed.
     */
    public function install()
    {
        if ($this->_plugin->install() &&
            $this->set_plugin_version($this->_plugin->version)) {
            return true;
        }

        return false;
    } // install

    /**
     * uninstall
     * This runs the uninstall function of the plugin and removes the row
     * from the update_info table to indicate that it isn't installed.
     */
    public function uninstall()
    {
        $this->_plugin->uninstall();

        $this->remove_plugin_version();
    } // uninstall

    /**
     * upgrade
     * This runs the upgrade function of the plugin (if it exists) and
     * updates the database to indicate our new version.
     */
    public function upgrade()
    {
        if (method_exists($this->_plugin, 'upgrade')) {
            if ($this->_plugin->upgrade()) {
                $this->set_plugin_version($this->_plugin->version);
            }
        }
    } // upgrade

    /**
     * load
     * This calls the plugin's load function
     */
    public function load($user)
    {
        $user->set_preferences();

        return $this->_plugin->load($user);
    }

    /**
     * get_plugin_version
     * This returns the version of the specified plugin
     */
    public static function get_plugin_version($plugin_name)
    {
        $db_results = \App\Models\Update_Info::where('key', '=', $plugin_name)->get();
        
        foreach ($db_results as $result) {
            return $result->value;
        }
            
        return false;
        
//         $db_results = DB::table('update_info')->where('key', $name)->get();
        return $value;
    } // get_plugin_version

    /**
     * set_plugin_version
     * This sets the plugin version in the update_info table
     */
    public function set_plugin_version($version)
    {
        $db = new Update_Info();
        
        $db->updateOrCreate(['key' => $this->name, 'value' => $this->_plugin->version]);

        return true;
    } // set_plugin_version

    /**
      * remove_plugin_version
     * This removes the version row from the db done on uninstall
     */
    public function remove_plugin_version()
    {
        $db = new Update_Info();
        $db->where('key', '=', $this->name)->delete();
        
        return true;
    } // remove_plugin_version
} //end plugin class
