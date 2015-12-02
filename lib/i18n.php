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
 * load_gettext
 * Sets up our local gettext settings.
 *
 * @return void
 */
function load_gettext()
{
    $lang    = AmpConfig::get('lang');
    $charset = AmpConfig::get('site_charset') ?: 'UTF-8';
    $locale  = $lang . '.' . $charset;
    //debug_event('i18n', 'Setting locale to ' . $locale, 5);
    T_setlocale(LC_MESSAGES, $locale);
    /* Bind the Text Domain */
    T_bindtextdomain('messages', AmpConfig::get('prefix') . "/locale/");
    T_bind_textdomain_codeset('messages', $charset);
    T_textdomain('messages');
    //debug_event('i18n', 'gettext is ' . (locale_emulation() ? 'emulated' : 'native'), 5);
} // load_gettext

/**
 * gettext_noop
 *
 * @param    string    $string
 * @return    string
 */
function gettext_noop($string)
{
    return $string;
}
