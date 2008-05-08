<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * get_themes
 * this looks in /themes and pulls all of the 
 * theme.cfg.php files it can find and returns an 
 * array of the results
 */
function get_themes() { 

	/* Open the themes dir and start reading it */
	$handle = @opendir(Config::get('prefix') . '/themes');

	if (!is_resource($handle)) { 
		 debug_event('theme',"Error unable to open Themes Directory",'2'); 
		 return array(); 
	}

	$results = array(); 

	while ($file = readdir($handle)) { 
		
		$full_file = Config::get('prefix') . '/themes/' . $file;
		/* See if it's a directory */
		if (is_dir($full_file) AND substr($file,0,1) != ".") { 
			$config_file = $full_file . '/theme.cfg.php';
			/* Open the theme.cfg.php file */
			$r = @parse_ini_file($config_file);
			$r['path'] = $file;
			$name = $r['name']; 
			$results[$name] = $r;
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
function get_theme($name) { 

	if (strlen($name) < 1) { return false; }

	$config_file = Config::get('prefix') . "/themes/" . $name . "/theme.cfg.php";
	$results = parse_ini_file($config_file);
	$results['path'] = $name;
	return $results;

} // get_theme

/*!
	@function get_theme_author
	@discussion returns the author of this theme
*/
function get_theme_author($theme_name) { 

	$theme_path = conf('prefix') . "/themes/" . conf('theme_name') . "/theme.cfg.php";
	$results = read_config($theme_path);

	return $results['author'];

} // get_theme_author

/*!
	@function theme_exists
	@discussion this function checks to make sure that a theme actually exists
*/
function theme_exists($theme_name) { 

	$theme_path = conf('prefix') . "/themes/" . $theme_name . "/theme.cfg.php";

	if (!file_exists($theme_path)) { 
		return false; 
	}

	return true;

} // theme_exists

?>
