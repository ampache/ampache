<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
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

// A collection of methods related to the user interface

class UI {

	private static $_classes;
	private static $_ticker;

	public function __construct($data) {
		return false;
	}

	/**
	 * access_denied
	 *
	 * Throw an error when they try to do something naughty.
	 */
	public static function access_denied($error = 'Access Denied') {
		// Clear any buffered crap
		ob_end_clean();
		header("HTTP/1.1 403 $error");
		require_once Config::get('prefix') . '/templates/show_denied.inc.php';
		exit;
	}

	/**
	 * check_iconv
	 *
	 * Checks to see whether iconv is available;
	 */
	public static function check_iconv() {
		if (function_exists('iconv') && function_exists('iconv_substr')) {
			return true;
		}
		return false;
	}

	/**
	 * check_ticker
	 *
	 * Stupid little cutesie thing to ratelimit output of long-running
	 * operations.
	 */
	public static function check_ticker() {
		if (!isset(self::$_ticker) || (time() > self::$_ticker + 1)) {
			self::$_ticker = time();
			return true;
		}

		return false;
	}

	/**
	 * flip_class
	 *
	 * First initialised with an array of two class names. Subsequent calls
	 * reverse the array then return the first element.
	 */
	public static function flip_class($classes = null) {
		if (is_array($classes)) {
			self::$_classes = $array;
		}
		else {
			self::$_classes = array_reverse(self::$_classes);
		}
		return self::$_classes[0];
	}

	/**
	 * show_header
	 *
	 * For now this just shows the header template
	 */
	public static function show_header() {
		require_once Config::get('prefix') . '/templates/header.inc.php';
	}

	/**
	 * show_footer
	 *
	 * Shows the footer template and possibly profiling info.
	 */
	public static function show_footer() {
		require_once Config::get('prefix') . '/templates/footer.inc.php';
		if (isset($_REQUEST['profiling'])) {
			Dba::show_profile();
		}
	}

	/**
	 * show_box_top
	 *
	 * This shows the top of the box.
	 */
	public static function show_box_top($title = '', $class = '') {
		require Config::get('prefix') . '/templates/show_box_top.inc.php';
	}

	/**
	 * show_box_bottom
	 *
	 * This shows the bottom of the box
	 */
	public static function show_box_bottom() {
		require Config::get('prefix') . '/templates/show_box_bottom.inc.php';
	}

	/**
	 * truncate
	 *
	 * Limit text to a certain length; adds an ellipsis if truncation was
	 * required.
	 */
	public static function truncate($text, $max = 27) {
		// If they want <3, we're having none of that
		if ($max <= 3) {
			debug_event('UI', "truncate called with $max, refusing to do stupid things to $text", 2);
			return $text;
		}

		if (self::check_iconv()) {
			$charset = Config::get('site_charset');
			if (iconv_strlen($text, $charset) > $max) {
				$text = iconv_substr($text, 0, $max - 3, $charset);
				$text .= iconv('ISO-8859-1', $charset, '...');
			}
		}
		else {
			if (strlen($text) > $max) {
				$text = substr($text, 0, $max - 3) . '...';
			}
		}

		return $text;
	}
}
