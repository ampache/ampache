<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Gettext\Translator;

/**
 * load_gettext
 * Sets up our local gettext settings.
 *
 * @return boolean
 */
function load_gettext()
{
    $lang   = AmpConfig::get('lang');
    $popath = AmpConfig::get('prefix') . '/locale/' . $lang . '/LC_MESSAGES/messages.po';

    $gettext = new Translator();
    if (file_exists($popath)) {
        $translations = Gettext\Translations::fromPoFile($popath);
        $gettext->loadTranslations($translations);
    }
    $gettext->register();

    return true;
} // load_gettext

/*
 * T_
 * Translate string
 * @param string $msgid
 * @return string
 */
/**
 * @param $msgid
 * @return mixed
 */
function T_($msgid)
{
    if (function_exists('__')) {
        return __($msgid);
    }

    return $msgid;
}

/**
 * @param $original
 * @param $plural
 * @param $value
 * @return mixed
 */
function nT_($original, $plural, $value)
{
    if (function_exists('n__')) {
        return n__($original, $plural, $value);
    }

    return $plural;
}
