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
	$lang = Config::get('lang');
	$charset = Config::get('site_charset') ?: 'UTF-8';
	$locale = $lang . '.' . $charset;
	debug_event('i18n', 'Setting locale to ' . $locale, 5);
	T_setlocale(LC_MESSAGES, $locale);
	/* Bind the Text Domain */
	T_bindtextdomain('messages', Config::get('prefix') . "/locale/");
	T_bind_textdomain_codeset('messages', $charset);
	T_textdomain('messages');
	debug_event('i18n', 'gettext is ' . (locale_emulation() ? 'emulated' : 'native'), 5);
} // load_gettext

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
