<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

/**
 * get_themes
 * this looks in /themes and pulls all of the
 * theme.cfg.php files it can find and returns an
 * array of the results
 * @return array
 */
function get_themes()
{
    /* Open the themes dir and start reading it */
    $handle = opendir(AmpConfig::get('prefix') . '/themes');

    if (!is_resource($handle)) {
        debug_event('themes', 'Failed to open /themes directory', 2);

        return array();
    }

    $results = array();
    while (($file = readdir($handle)) !== false) {
        if ((string) $file !== '.' && (string) $file !== '..') {
            debug_event('themes', "Checking $file", 5);
            $cfg = get_theme($file);
            if ($cfg !== null) {
                $results[$cfg['name']] = $cfg;
            }
        }
    } // end while directory
    // Sort by the theme name
    ksort($results);

    return $results;
} // get_themes

/**
 * @function get_theme
 * @discussion get a single theme and read the config file
 * then return the results
 * @param string $name
 * @return array|boolean|false|mixed|null
 */
function get_theme($name)
{
    static $_mapcache = array();

    if (strlen((string) $name) < 1) {
        return false;
    }

    $name = strtolower((string) $name);

    if (isset($_mapcache[$name])) {
        return $_mapcache[$name];
    }

    $config_file = AmpConfig::get('prefix') . "/themes/" . $name . "/theme.cfg.php";
    if (file_exists($config_file)) {
        $results         = parse_ini_file($config_file);
        $results['path'] = $name;
        $results['base'] = explode(',', (string) $results['base']);
        $nbbases         = count($results['base']);
        for ($count = 0; $count < $nbbases; $count++) {
            $results['base'][$count] = explode('|', $results['base'][$count]);
        }
        $results['colors'] = explode(',', (string) $results['colors']);
    } else {
        $results = null;
    }
    $_mapcache[$name] = $results;

    return $results;
} // get_theme

/**
 * @function get_theme_author
 * @discussion returns the author of this theme
 * @param string $theme_name
 * @return string
 */
function get_theme_author($theme_name)
{
    $theme_path = AmpConfig::get('prefix') . '/themes/' . $theme_name . '/theme.cfg.php';
    $results    = read_config($theme_path);

    return $results['author'];
} // get_theme_author

/**
 * @function theme_exists
 * @discussion this function checks to make sure that a theme actually exists
 * @param string $theme_name
 * @return boolean
 */
function theme_exists($theme_name)
{
    $theme_path = AmpConfig::get('prefix') . '/themes/' . $theme_name . '/theme.cfg.php';

    if (!file_exists($theme_path)) {
        return false;
    }

    return true;
} // theme_exists
