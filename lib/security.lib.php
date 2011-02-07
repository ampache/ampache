<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Debug Library
 *
 * This library is loaded when somehow our mojo has
 * been lost, it contains functions for checking sql
 * connections, web paths etc..
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @category	Security
 * @package	Library
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @author	momo-i <webmaster@momo-i.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 3.6
 */

/**
 * check_ampache_version
 *
 * This function checks latest ampache stable from Ampache web site.
 * If new version found, return error message.
 *
 * @return	string
 */
function check_ampache_version() {

	$my_ampache = Config::get('version');
	if(preg_match('#-#', $my_ampache)) {
		$my_ampache = explode('-', $my_ampache);
		$my_ampache = $my_ampache[0];
	}

	$latest_ampache = get_latest('ampache');
	$latest_ampache = $latest_ampache['ampache'];

	if(version_compare($my_ampache, $latest_ampache, '>=')) {
		$results = debug_result(_('No problem found.'),1);
	}
	else {
		$results = debug_result(sprintf(_('You are running old ampache: %s'), $my_ampache),0);
	}

	return $results;

} // check_ampache_version

/**
 * check_php_version
 *
 * This function checks latest PHP stable from php web site.
 * If new version found, return error message.
 * Also, if version is older than 5.2.x, return error message.
 *
 * @return	string
 */
function check_php_version() {

	$my_php = PHP_VERSION;

	$latest_php = get_latest('php');
	if (preg_match('#^5\.3#', $my_php)) {
		$latest_php = $latest_php['php5.3'];
	}
	elseif (preg_match('#^5\.2#', $my_php)) {
		$latest_php = $latest_php['php5.2'];
	}
	else {
		$results = debug_result(sprintf(_('Your PHP version may be too old: %s'), $my_php),0);
		return $results;
	}
	if(version_compare($my_php, $latest_php, '>=')) {
		$results = debug_result(_('No probrem found.'),1);
	}
	else {
		$results = debug_result(sprintf(_('You are running old php: %s'), $my_php),0);
	}

	return $results;

} // check_php_version

/**
 * get_latest
 *
 * This function gets from each sites.
 * Pattern may change in a future...
 *
 * @param	string	$type	Type you want to get.
 * @return	array	return version number.
 */
function get_latest($type = null) {

	if (!$type) { return false; }
	$version = array();

	switch ($type) {
		case 'php':
			$url = "http://www.php.net/downloads.php";
			$pattern = '#<h1 id="v(.*)">PHP (.*)</h1>#';
		break;
		case 'ampache':
			$url = "http://ampache.org/download/";
			$pattern = '#<a onclick=.*>(.*) Stable</a>#';
		break;
		default:
			$url = "";
		break;
	}
	if (!$url) { return false; }

	if (!extension_loaded('curl')) {
		return false;
	}
	$ch = curl_init($url);
	$phost = Config::get('proxy_host');
	$pport = Config::get('proxy_port');
	$header = array(
		"User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 1.1.4322; OfficeLiveConnector.1.3; OfficeLivePatch.0.0)",
		"Accept: */*",
		"Accept-Encoding: none",
		"Cache-Control: no-cache",
		"Pragma: no-cache",
		"Connection: keep-alive");
	if (isset($phost) && isset($pport)) {
		curl_setopt($ch, CURLOPT_PROXY, $phost);
		curl_setopt($ch, CURLOPT_PROXYPORT, $pport);
	}
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	ob_start();

	curl_exec($ch);
	curl_close($ch);

	$body = ob_get_contents();
	ob_end_clean();

	preg_match_all($pattern, $body, $versions);
	if (strcmp($type, "ampache") == 0) {
		$version['ampache'] = $versions[1][0];
	}
	elseif (strcmp($type, "php") == 0) {
		$version['php5.3'] = $versions[1][0];
		$version['php5.2'] = $versions[1][1];
	}

	return $version;

} // get_latest

/**
 * check_security
 *
 * This function tests wheter vulnerable settings on your php.ini
 * 
 * @return	array	Show security messages, if found.
 */
function check_security() {

} // check_security
?>
