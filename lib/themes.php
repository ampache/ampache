<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

/**
 * get_themes
 * this looks in /themes and pulls all of the
 * theme.cfg.php files it can find and returns an
 * array of the results
 */
function get_themes()
{
    /* Open the themes dir and start reading it */
    $handle = opendir(AmpConfig::get('prefix') . '/themes');

    if (!is_resource($handle)) {
        debug_event('theme', 'Failed to open /themes directory', 2);
        return array();
    }

    $results = array();
    while (($f = readdir($handle)) !== false) {
        debug_event('theme', "Checking $f", 5);
        $cfg = get_theme($f);
        if ($cfg !== null) {
            $results[$cfg['name']] = $cfg;
        }
    } // end while directory

    // Sort by the theme name
    ksort($results);

    return $results;
} // get_themes

/*!
    @function get_theme
    @discussion get a single theme and read the config file
        then return the results
*/
function get_theme($name)
{
    static $_mapcache = array();
            
    if (strlen($name) < 1) {
        return false;
    }
    
    $name = strtolower($name);
    
    if (isset($_mapcache[$name])) {
        return $_mapcache[$name];
    }

    $config_file = AmpConfig::get('prefix') . "/themes/" . $name . "/theme.cfg.php";
    if (file_exists($config_file)) {
        $results         = parse_ini_file($config_file);
        $results['path'] = $name;
        $results['base'] = explode(',', $results['base']);
        $nbbases         = count($results['base']);
        for ($i = 0; $i < $nbbases; $i++) {
            $results['base'][$i] = explode('|', $results['base'][$i]);
        }
        $results['colors'] = explode(',', $results['colors']);
    } else {
        debug_event('theme', $config_file . ' not found.', 3);
        $results = null;
    }
    $_mapcache[$name] = $results;
    
    return $results;
} // get_theme

/*!
    @function get_theme_author
    @discussion returns the author of this theme
*/
function get_theme_author($theme_name)
{
    $theme_path = AmpConfig::get('prefix') . '/themes/' . $theme_name . '/theme.cfg.php';
    $results    = read_config($theme_path);

    return $results['author'];
} // get_theme_author

/*!
    @function theme_exists
    @discussion this function checks to make sure that a theme actually exists
*/
function theme_exists($theme_name)
{
    $theme_path = AmpConfig::get('prefix') . '/themes/' . $theme_name . '/theme.cfg.php';

    if (!file_exists($theme_path)) {
        return false;
    }

    return true;
} // theme_exists
