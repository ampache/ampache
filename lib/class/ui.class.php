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
}
