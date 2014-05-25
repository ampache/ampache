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
    $theme_cfg = '/theme.cfg.php';

    while (($f = readdir($handle)) !== false) {
        debug_event('theme', "Checking $f", 5);
        $file = AmpConfig::get('prefix') . '/themes/' . $f;
        if (file_exists($file . $theme_cfg)) {
            debug_event('theme', "Loading $theme_cfg from $f", 5);
            $r = parse_ini_file($file . $theme_cfg);
            $r['path'] = $f;
            $results[$r['name']] = $r;
        } else {
            debug_event('theme', "$theme_cfg not found in $f", 5);
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
    if (strlen($name) < 1) { return false; }

    $config_file = AmpConfig::get('prefix') . "/themes/" . $name . "/theme.cfg.php";
    $results = parse_ini_file($config_file);
    $results['path'] = $name;
    return $results;

} // get_theme

/*!
    @function get_theme_author
    @discussion returns the author of this theme
*/
function get_theme_author($theme_name)
{
    $theme_path = AmpConfig::get('prefix') . '/themes/' . $theme_name . '/theme.cfg.php';
    $results = read_config($theme_path);

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
