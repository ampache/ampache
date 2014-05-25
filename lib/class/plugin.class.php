<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class Plugin
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
    public function __construct($name)
    {
        /* Load the plugin */
        if (!$this->_get_info($name)) {
            return false;
        }

        return true;

    } // Constructor


    /**
     * _get_info
     * This actually loads the config file for the plugin the name of the
     * class contained within the config file must be Plugin[NAME OF FILE]
     */
    public function _get_info($name)
    {
        /* Require the file we want */
        require_once AmpConfig::get('prefix') . '/modules/plugins/' . $name . '.plugin.php';

        $plugin_name = "Ampache$name";

        $this->_plugin = new $plugin_name();

        if (!$this->is_valid()) {
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
        $results = array();

        // Open up the plugin dir
        $handle = opendir(AmpConfig::get('prefix') . '/modules/plugins');

        if (!is_resource($handle)) {
            debug_event('Plugins','Unable to read plugins directory','1');
        }

        // Recurse the directory
        while ($file = readdir($handle)) {
            // Ignore non-plugin files
            if (substr($file,-10,10) != 'plugin.php') { continue; }
            if (is_dir($file)) { continue; }
            $plugin_name = basename($file,'.plugin.php');
            if ($type != '') {
                $plugin = new Plugin($plugin_name);
                if (! Plugin::is_installed($plugin->_plugin->name)) {
                    debug_event('Plugins', 'Plugin ' . $plugin->_plugin->name . ' is not installed, skipping', 5);
                    continue;
                }
                if (! $plugin->is_valid()) {
                    debug_event('Plugins', 'Plugin ' . $plugin_name . ' is not valid, skipping', 5);
                    continue;
                }
                if (! method_exists($plugin->_plugin, $type)) {
                    debug_event('Plugins', 'Plugin ' . $plugin_name . ' does not support ' . $type . ', skipping', 5);
                    continue;
                }
            }
            // It's a plugin record it
            $results[$plugin_name] = $plugin_name;
        } // end while

        // Little stupid but hey
        ksort($results);

        return $results;

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
        if (!method_exists($this->_plugin,'install')) {
            return false;
        }

        if (!method_exists($this->_plugin,'uninstall')) {
            return false;
        }

        if (!method_exists($this->_plugin,'load')) {
            return false;
        }

        /* Make sure it's within the version confines */
        $db_version = $this->get_ampache_db_version();

        if ($db_version < $this->_plugin->min_ampache) {
            return false;
        }

        if ($db_version > $this->_plugin->max_ampache) {
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
        $name = Dba::escape('Plugin_' . $plugin_name);

        $sql = "SELECT * FROM `update_info` WHERE `key`='$name'";
        $db_results = Dba::read($sql);

        if ($results = Dba::fetch_assoc($db_results)) {
            return $results['value'];
        }

        return false;

    } // get_plugin_version

    /**
     * get_ampache_db_version
     * This function returns the Ampache database version
     */
    public function get_ampache_db_version()
    {
        $sql = "SELECT * FROM `update_info` WHERE `key`='db_version'";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results['value'];

    } // get_ampache_db_version

    /**
     * set_plugin_version
     * This sets the plugin version in the update_info table
     */
    public function set_plugin_version($version)
    {
        $name         = Dba::escape('Plugin_' . $this->_plugin->name);
        $version    = Dba::escape($version);

        $sql = "REPLACE INTO `update_info` SET `key`='$name', `value`='$version'";
        Dba::write($sql);

        return true;

    } // set_plugin_version

    /**
      * remove_plugin_version
     * This removes the version row from the db done on uninstall
     */
    public function remove_plugin_version()
    {
        $name    = Dba::escape('Plugin_' . $this->_plugin->name);

        $sql = "DELETE FROM `update_info` WHERE `key`='$name'";
        Dba::write($sql);

        return true;

    } // remove_plugin_version

} //end plugin class
