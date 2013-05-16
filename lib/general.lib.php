<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
 * set_memory_limit
 * This function attempts to change the php memory limit using init_set.
 * Will never reduce it below the current setting.
 */
function set_memory_limit($new_limit) {

    $current_limit = ini_get('memory_limit');
    if ($current_limit == -1) {
        return;
    }

    $current_limit = UI::unformat_bytes($current_limit);
    $new_limit = UI::unformat_bytes($new_limit);

    if ($current_limit < $new_limit) {
        ini_set (memory_limit, $new_limit);
    }

} // set_memory_limit

/**
 * generate_password
 * This generates a random password of the specified length
 */
function generate_password($length) {

    $vowels = 'aAeEuUyY12345';
    $consonants = 'bBdDgGhHjJmMnNpPqQrRsStTvVwWxXzZ6789';
    $password = '';

    $alt = time() % 2;

    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand(0,strlen($consonants)-1))];
        $alt = 0;
        }
        else {
            $password .= $vowels[(rand(0,strlen($vowels)-1))];
            $alt = 1;
        }
    }

    return $password;

} // generate_password

/**
 * scrub_in
 * Run on inputs, stuff that might get stuck in our db
 */
function scrub_in($input) {

    if (!is_array($input)) {
        return stripslashes(htmlspecialchars(strip_tags($input), ENT_QUOTES, Config::get('site_charset')));
    }
    else {
        $results = array();
        foreach($input as $item) {
            $results[] = scrub_in($item);
        }
        return $results;
    }
} // scrub_in

/**
 * scrub_out
 * This function is used to escape user data that is getting redisplayed
 * onto the page, it htmlentities the mojo
 */
function scrub_out($string) {

    return htmlentities($string, ENT_QUOTES, Config::get('site_charset'));

} // scrub_out

/**
 * unhtmlentities
 * Undoes htmlentities()
 */
function unhtmlentities($string) {

    return html_entity_decode($string, ENT_QUOTES, Config::get('site_charset'));

} //unhtmlentities

/**
 * scrub_arg
 *
 * This function behaves like escapeshellarg, but isn't broken
 */
function scrub_arg($arg)
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return '"' . str_replace(array('"', '%'), array('', ''), $arg) . '"';
    } else {
        return "'" . str_replace("'", "'\\''", $arg) . "'";
    }
}

/**
 * make_bool
 * This takes a value and returns what we consider to be the correct boolean
 * value. We need a special function because PHP considers "false" to be true.
 */
function make_bool($string) {

    if (strcasecmp($string,'false') == 0) {
        return false;
    }

    return (bool)$string;

} // make_bool

/**
 * invert_bool
 * This returns the opposite of what you've got
 */
function invert_bool($value) {

    return make_bool($value) ? false : true;

} // invert_bool

/**
 * get_languages
 * This function does a dir of ./locale and pulls the names of the
 * different languages installed, this means that all you have to do
 * is drop one in and it will show up on the context menu. It returns
 * in the form of an array of names
 */
function get_languages() {

    /* Open the locale directory */
    $handle    = @opendir(Config::get('prefix') . '/locale');

    if (!is_resource($handle)) {
        debug_event('language','Error unable to open locale directory','1');
    }

    $results = array();

    /* Prepend English */
    $results['en_US'] = 'English (US)';

    while ($file = readdir($handle)) {

        $full_file = Config::get('prefix') . '/locale/' . $file;

        /* Check to see if it's a directory */
        if (is_dir($full_file) AND substr($file,0,1) != '.' AND $file != 'base') {

            switch($file) {
                case 'af_ZA'; $name = 'Afrikaans'; break; /* Afrikaans */
                case 'ca_ES'; $name = 'Catal&#224;'; break; /* Catalan */
                case 'cs_CZ'; $name = '&#x010c;esky'; break; /* Czech */
                case 'da_DK'; $name = 'Dansk'; break; /* Danish */
                case 'de_DE'; $name = 'Deutsch'; break; /* German */
                case 'en_US'; $name = 'English (US)'; break; /* English */
                case 'en_GB'; $name = 'English (UK)'; break; /* English */
                case 'es_ES'; $name = 'Espa&#241;ol'; break; /* Spanish */
                case 'es_MX'; $name = 'Espa&#241;ol (MX)'; break; /* Spanish */
                case 'es_AR'; $name = 'Espa&#241;ol (AR)'; break; /* Spanish */
                case 'et_EE'; $name = 'Eesti'; break; /* Estonian */
                case 'eu_ES'; $name = 'Euskara'; break; /* Basque */
                case 'fr_FR'; $name = 'Fran&#231;ais'; break; /* French */
                case 'ga_IE'; $name = 'Gaeilge'; break; /* Irish */
                case 'el_GR'; $name = 'Greek'; break; /* Greek */
                case 'is_IS'; $name = 'Icelandic'; break; /* Icelandic */
                case 'it_IT'; $name = 'Italiano'; break; /* Italian */
                case 'lv_LV'; $name = 'Latvie&#353;u'; break; /* Latvian */
                case 'lt_LT'; $name = 'Lietuvi&#371;'; break; /* Lithuanian */
                case 'hu_HU'; $name = 'Magyar'; break; /* Hungarian */
                case 'nl_NL'; $name = 'Nederlands'; break; /* Dutch */
                case 'no_NO'; $name = 'Norsk bokm&#229;l'; break; /* Norwegian */
                case 'pl_PL'; $name = 'Polski'; break; /* Polish */
                case 'pt_BR'; $name = 'Portugu&#234;s Brasileiro'; break; /* Portuguese */
                case 'pt_PT'; $name = 'Portugu&#234;s'; break; /* Portuguese */
                case 'ro_RO'; $name = 'Rom&#226;n&#259;'; break; /* Romanian */
                case 'sk_SK'; $name = 'Sloven&#269;ina'; break; /* Slovak */
                case 'sl_SI'; $name = 'Sloven&#353;&#269;ina'; break; /* Slovenian */
                case 'sr_CS'; $name = 'Srpski'; break; /* Serbian */
                case 'fi_FI'; $name = 'Suomi'; break; /* Finnish */
                case 'sv_SE'; $name = 'Svenska'; break; /* Swedish */
                case 'uk_UA'; $name = 'Українська'; break; /* Ukrainian */
                case 'vi_VN'; $name = 'Ti&#7871;ng Vi&#7879;t'; break; /* Vietnamese */
                case 'tr_TR'; $name = 'T&#252;rk&#231;e'; break; /* Turkish */
                case 'bg_BG'; $name = '&#x0411;&#x044a;&#x043b;&#x0433;&#x0430;&#x0440;&#x0441;&#x043a;&#x0438;'; break; /* Bulgarian */
                case 'ru_RU'; $name = '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'; break; /* Russian */
                case 'zh_CN'; $name = '&#31616;&#20307;&#20013;&#25991;'; break; /* Chinese */
                case 'zn_TW'; $name = '&#32321;&#39636;&#20013;&#25991;'; break; /* Chinese */
                case 'ko_KR'; $name = '&#xd55c;&#xad6d;&#xb9d0;'; break; /* Korean */
                case 'ja_JP'; $name = '&#x65e5;&#x672c;&#x8a9e;'; break; /* Japanese */
                case 'nb_NO'; $name = 'Norsk'; break; /* Norwegian */
                /* These languages are right to left. */
                case 'ar_SA'; $name = '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;'; break; /* Arabic */
                case 'he_IL'; $name = '&#1506;&#1489;&#1512;&#1497;&#1514;'; break; /* Hebrew */
                case 'fa_IR'; $name = '&#1601;&#1575;&#1585;&#1587;&#1610;'; break; /* Farsi */
                default: $name = T_('Unknown'); break;
            } // end switch


            $results[$file] = $name;
        }

    } // end while

    return $results;

} // get_languages

/**
 * is_rtl
 * This checks whether to be a rtl language.
 */
function is_rtl($locale) {
    return in_array($locale, array("he_IL", "fa_IR", "ar_SA"));
}

/**
 * translate_pattern_code
 * This just contains a keyed array which it checks against to give you the
 * 'tag' name that said pattern code corrasponds to. It returns false if nothing
 * is found.
 */
function translate_pattern_code($code) {

    $code_array = array('%A'=>'album',
            '%a'=>'artist',
            '%c'=>'comment',
            '%g'=>'genre',
            '%T'=>'track',
            '%t'=>'title',
            '%y'=>'year',
            '%o'=>'zz_other');

    if (isset($code_array[$code])) {
        return $code_array[$code];
    }

    return false;

} // translate_pattern_code

/**
 * generate_config
 *
 * This takes an array of results and re-generates the config file
 * this is used by the installer and by the admin/system page
 */
function generate_config($current) {
    // Start building the new config file
    $distfile = Config::get('prefix') . '/config/ampache.cfg.php.dist';
    $handle = fopen($distfile,'r');
    $dist = fread($handle,filesize($distfile));
    fclose($handle);

    $data = explode("\n",$dist);

    foreach ($data as $line) {  
        if (preg_match("/^;?([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$line,$matches)            
            || preg_match("/^;?([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $line, $matches)       
            || preg_match("/^;?([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$line,$matches)) {

            $key    = $matches[1];
            $value  = $matches[2];

            // Put in the current value
            if ($key == 'config_version') {
                $line = $key . ' = ' . escape_ini($value);
            }
            elseif (isset($current[$key])) {
                $line = $key . ' = "' . escape_ini($current[$key]) . '"';
                unset($current[$key]);
            } 
        }

        $final .= $line . "\n";
    }
    
    return $final;
}

/**
 * escape_ini
 *
 * Escape a value used for inserting into an ini file. 
 * Won't quote ', like addslashes does.
 */
function escape_ini($str) {
    return str_replace('"', '\"', $str);
}

?>
