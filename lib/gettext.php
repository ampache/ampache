<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Gettext Library
 *
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

/**
 * load_gettext
 * Sets up our local gettext settings.
 *
 * @return void
 */
function load_gettext() {
	/* If we have gettext */
	if (function_exists('bindtextdomain')) {
		$lang = Config::get('lang');
		putenv("LANG=" . $lang);
		putenv("LANGUAGE=" . $lang);
		/* Try lang, lang + charset and lang + utf-8 */
		setlocale(LC_ALL,
				$lang,
				$lang . '.UTF-8', //. Config::get('site_charset'),
				$lang . '.UTF-8',
				$lang . '.UTF-8',
				$lang . '.UTF-8'); // . Config::get('lc_charset'));

		/* Bind the Text Domain */
		bindtextdomain('messages', Config::get('prefix') . "/locale/");
		textdomain('messages');
		if (function_exists('bind_textdomain_codeset')) {
			bind_textdomain_codeset('messages',Config::get('site_charset'));
		} // if we can codeset the textdomain

	} // If bindtext domain exists

} // load_gettext

/**
 * __
 * This function does the same as _ on the supplied
 * string, but also does a str_replace on the supplied
 * vars
 *
 * @param	string	$string
 * @param	string	$subject
 * @param	string	$replace
 * @return	string
 */
function __($string,$subject,$replace) {

	$translated = _($string);
	$result = str_replace($subject,$replace,$translated);
	return $result;

} // __

/**
 * gettext_noop
 *
 * @param	string	$string
 * @return	string
 */
function gettext_noop($string) {
	return $string;
}

?>
