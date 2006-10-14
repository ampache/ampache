<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*!
	@function load_gettext
	@discussion sets the local
*/
function load_gettext() { 
	/* If we have gettext */
	if (function_exists('bindtextdomain')) { 
		$lang = conf('lang');
		putenv("LANG=" . $lang);
		putenv("LANGUAGE=" . $lang);
		/* Try lang, lang + charset and lang + utf-8 */
		setlocale(LC_ALL, 
				$lang,
				$lang . '.'. conf('site_charset'),
				$lang . '.UTF-8',
				$lang . '.utf-8',
				$lang . '.' . conf('lc_charset'));

		/* Bind the Text Domain */
		bindtextdomain('messages', conf('prefix') . "/locale/");
		textdomain('messages');
		if (function_exists('bind_textdomain_codeset')) { 
			bind_textdomain_codeset('messages',conf('site_charset'));
		} // if we can codeset the textdomain

	} // If bindtext domain exists

} // load_gettext


?>
