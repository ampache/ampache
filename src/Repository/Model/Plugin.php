<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Plugin\PluginEnum;

class Plugin
{
    /** @var null|string $name */
    public $name;
    public $_plugin;

    /**
     * Constructor
     * This constructor loads the Plugin config file which defines how to
     * install/uninstall the plugin from Ampache's database
     * @param string $name
     */
    public function __construct($name)
    {
        $this->has_info($name);
    } // Constructor

    /**
     * has_info
     * @param string $cname
     */
    private function has_info($cname): bool
    {
        $controller = PluginEnum::LIST[strtolower($cname)] ?? null;
        if ($controller === null) {
            debug_event(__CLASS__, 'Cannot find plugin `' . $cname . '`.', 1);
            $this->_plugin = null;
            $this->name    = null;

            return false;
        }
        $this->_plugin = new $controller();
        $this->name    = $cname;

        return $this->is_valid();
    }

    /**
     * get_plugins
     * This returns an array of plugin names
     * @param string $type
     * @return array
     */
    public static function get_plugins($type = '')
    {
        // make static cache for optimization when multiple call
        static $plugins_list = array();
        if (isset($plugins_list[$type])) {
            return $plugins_list[$type];
        }

        $plugins_list[$type] = array();

        foreach (PluginEnum::LIST as $name => $className) {
            if ($type != '') {
                $plugin = new Plugin($name);
                if ($plugin->_plugin === null) {
                    continue;
                }
                if (!Plugin::is_installed($plugin->_plugin->name)) {
                    continue;
                }
                if (!$plugin->is_valid()) {
                    debug_event(__CLASS__, 'Plugin ' . $name . ' is not valid, skipping', 6);
                    continue;
                }
                if (!method_exists($plugin->_plugin, $type)) {
                    continue;
                }
            }
            // It's a plugin record it
            $plugins_list[$type][$name] = $name;
        }

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
    public function is_valid(): bool
    {
        if ($this->_plugin === null) {
            return false;
        }
        /* Check the plugin to make sure it's got the needed vars */
        if (!strlen((string)$this->_plugin->name)) {
            return false;
        }
        if (!strlen((string)$this->_plugin->description)) {
            return false;
        }
        if (!strlen((string)$this->_plugin->version)) {
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
        if (!method_exists($this->_plugin, 'upgrade')) {
            // TODO mark upgrade as required for Ampache 7+
            debug_event(__CLASS__, 'WARNING: Plugin missing upgrade method. ' . $this->_plugin->name . '`.', 1);
        }

        /* Make sure it's within the version confines */
        $db_version = $this->get_ampache_db_version();

        if ($db_version < $this->_plugin->min_ampache) {
            return false;
        }

        if ($db_version > $this->_plugin->max_ampache) {
            return false;
        }

        return true;
    } // is_valid

    /**
     * is_installed
     * This checks to see if the specified plugin is currently installed in
     * the database, it doesn't check the files for integrity
     * @param $plugin_name
     */
    public static function is_installed($plugin_name): int
    {
        /* All we do is check the version */
        return self::get_plugin_version($plugin_name);
    } // is_installed

    /**
     * install
     * This runs the install function of the plugin and inserts a row into
     * the update_info table to indicate that it's installed.
     */
    public function install(): bool
    {
        if ($this->_plugin !== null && $this->_plugin->install()) {
            $this->set_plugin_version($this->_plugin->version);

            return true;
        }

        return false;
    } // install

    /**
     * uninstall
     * This runs the uninstall function of the plugin and removes the row
     * from the update_info table to indicate that it isn't installed.
     */
    public function uninstall(): bool
    {
        if ($this->_plugin !== null && method_exists($this->_plugin, 'uninstall')) {
            if ($this->_plugin->uninstall()) {
                $this->remove_plugin_version();

                return true;
            }
        }

        return false;

    } // uninstall

    /**
     * upgrade
     * This runs the upgrade function of the plugin (if it exists) and
     * updates the database to indicate our new version.
     */
    public function upgrade(): bool
    {
        if ($this->_plugin !== null && method_exists($this->_plugin, 'upgrade')) {
            if ($this->_plugin->upgrade()) {
                $this->set_plugin_version($this->_plugin->version);

                return true;
            }
        }

        return false;
    } // upgrade

    /**
     * load
     * This calls the plugin's load function
     * @param User $user
     */
    public function load($user): bool
    {
        if ($this->_plugin === null) {
            return false;
        }
        $user->set_preferences();

        return $this->_plugin->load($user);
    }

    /**
     * get_plugin_version
     * This returns the version of the specified plugin
     * @param $plugin_name
     */
    public static function get_plugin_version($plugin_name): int
    {
        $name       = Dba::escape('Plugin_' . $plugin_name);
        $sql        = "SELECT `key`, `value` FROM `update_info` WHERE `key` = ?";
        $db_results = Dba::read($sql, array($name));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['value'];
        }

        return 0;
    } // get_plugin_version

    /**
     * Check if an update is available.
     */
    public static function is_update_available(): bool
    {
        foreach (PluginEnum::LIST as $name => $className) {
            $plugin = new Plugin($name);
            if ($plugin->_plugin !== null) {
                $installed_version = self::get_plugin_version($plugin->_plugin->name);
                // if any plugin needs an update then you need to update
                if ($installed_version > 0 && $installed_version < $plugin->_plugin->version) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check all plugins for updates and update them if required.
     */
    public static function update_all(): void
    {
        foreach (PluginEnum::LIST as $name => $className) {
            $plugin            = new Plugin($name);
            $installed_version = self::get_plugin_version($plugin->_plugin->name);
            if ($installed_version > 0 && $installed_version < $plugin->_plugin->version) {
                if ($plugin->_plugin !== null && method_exists($plugin->_plugin, 'upgrade')) {
                    if ($plugin->_plugin->upgrade()) {
                        $plugin->set_plugin_version($plugin->_plugin->version);
                    }
                }
            }
        }
    }

    /**
     * get_ampache_db_version
     * This function returns the Ampache database version
     */
    public function get_ampache_db_version(): string
    {
        $sql        = "SELECT `key`, `value` FROM `update_info` WHERE `key`='db_version'";
        $db_results = Dba::read($sql);
        $results    = Dba::fetch_assoc($db_results);

        return (string)$results['value'];
    } // get_ampache_db_version

    /**
     * set_plugin_version
     * This sets the plugin version in the update_info table
     * @param $version
     */
    public function set_plugin_version($version): void
    {
        if ($this->_plugin === null) {
            return;
        }
        $name    = Dba::escape('Plugin_' . $this->_plugin->name);
        $version = (int)Dba::escape($version);

        $sql = "REPLACE INTO `update_info` SET `key` = ?, `value`= ?";
        Dba::write($sql, array($name, $version));
    } // set_plugin_version

    /**
     * remove_plugin_version
     * This removes the version row from the db done on uninstall
     */
    public function remove_plugin_version(): void
    {
        if ($this->_plugin === null) {
            return;
        }
        $name = Dba::escape('Plugin_' . $this->_plugin->name);
        $sql  = "DELETE FROM `update_info` WHERE `key`='$name'";
        Dba::write($sql);
    } // remove_plugin_version

    /**
     * Display Plugin Update information and update links.
     */
    public static function show_update_available(): void
    {
        $web_path = AmpConfig::get('web_path');
        echo '<div id="autoupdate">';
        echo '<span>' . T_('Update available') . '</span> ' . T_('You have Plugins that need an update!');
        echo '<br />';
        echo '<a class="nohtml" href="' . $web_path . '/update.php?type=sources&action=update_plugins">' . T_('Update Plugins automatically') . '</a> | <a class="nohtml" href="' . $web_path . '/admin/modules.php?action=show_plugins">' . T_('Manage Plugins') . '</a>';
        echo '<br />';
        echo '</div>';
    }
}
