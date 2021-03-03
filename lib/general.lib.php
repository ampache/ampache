<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * set_memory_limit
 * This function attempts to change the php memory limit using init_set.
 * Will never reduce it below the current setting.
 * @param $new_limit
 */
function set_memory_limit($new_limit)
{
    $current_limit = ini_get('memory_limit');
    if ($current_limit == -1) {
        return;
    }

    $current_limit = UI::unformat_bytes($current_limit);
    $new_limit     = UI::unformat_bytes($new_limit);

    if ($current_limit < $new_limit) {
        ini_set('memory_limit', $new_limit);
    }
} // set_memory_limit

/**
 * generate_password
 * This generates a random password of the specified length
 * or will use a random length between 14-20
 *
 * @param integer $length (optional)
 * @return string
 */
function generate_password($length = null)
{
    // set a random password length so it's not as easy to guess
    if ($length === null) {
        $length = rand(14, 20);
    }
    $strong   = true;
    $string   = openssl_random_pseudo_bytes((int) ceil($length * 0.67), $strong);
    $encode   = str_replace('=', '', base64_encode($string));

    return strtr($encode, '+/', '^*');
} // generate_password

/**
 * scrub_in
 * Run on inputs, stuff that might get stuck in our db
 * @param string|array $input
 * @return string|array
 */
function scrub_in($input)
{
    if (!is_array($input)) {
        return stripslashes(htmlspecialchars(strip_tags((string) $input), ENT_NOQUOTES, AmpConfig::get('site_charset')));
    } else {
        $results = array();
        foreach ($input as $item) {
            $results[] = scrub_in((string) $item);
        }

        return $results;
    }
} // scrub_in

/**
 * scrub_out
 * This function is used to escape user data that is getting redisplayed
 * onto the page, it htmlentities the mojo
 * This is the inverse of the scrub_in function
 * @param string|null $string
 * @return string
 */
function scrub_out($string)
{
    if ($string === null) {
        return '';
    }

    return htmlentities((string) $string, ENT_NOQUOTES, AmpConfig::get('site_charset'));
} // scrub_out

/**
 * unhtmlentities
 * Undoes htmlentities()
 * @param string $string
 * @return string
 */
function unhtmlentities($string)
{
    return html_entity_decode((string) $string, ENT_QUOTES, AmpConfig::get('site_charset'));
} // unhtmlentities

/**
 * scrub_arg
 *
 * This function behaves like escapeshellarg, but isn't broken
 * @param $arg
 * @return string
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
 *
 * @param string $string
 * @return boolean
 */
function make_bool($string)
{
    if ($string === null) {
        return false;
    }
    if (strcasecmp((string) $string, 'false') == 0 || $string == '0') {
        return false;
    }

    return (bool) $string;
} // make_bool

/**
 * invert_bool
 * This returns the opposite of what you've got
 * @param $value
 * @return boolean
 */
function invert_bool($value)
{
    return make_bool($value) ? false : true;
} // invert_bool

/**
 * get_languages
 * This function does a dir of ./locale and pulls the names of the
 * different languages installed, this means that all you have to do
 * is drop one in and it will show up on the context menu. It returns
 * in the form of an array of names
 * @return array
 */
function get_languages()
{
    /* Open the locale directory */
    $handle = opendir(AmpConfig::get('prefix') . '/locale');

    if (!is_resource($handle)) {
        debug_event('general.lib', 'Error unable to open locale directory', 1);
    }

    $results = array();

    while (false !== ($file = readdir($handle))) {
        $full_file = AmpConfig::get('prefix') . '/locale/' . $file;

        /* Check to see if it's a directory */
        if (is_dir($full_file) && substr($file, 0, 1) != '.' && $file != 'base') {
            switch ($file) {
                case 'af_ZA':
                    $name = 'Afrikaans';
                    break; /* Afrikaans */
                case 'bg_BG':
                    $name = '&#x0411;&#x044a;&#x043b;&#x0433;&#x0430;&#x0440;&#x0441;&#x043a;&#x0438;';
                    break; /* Bulgarian */
                case 'ca_ES':
                    $name = 'Catal&#224;';
                    break; /* Catalan */
                case 'cs_CZ':
                    $name = '&#x010c;esky';
                    break; /* Czech */
                case 'da_DK':
                    $name = 'Dansk';
                    break; /* Danish */
                case 'de_DE':
                    $name = 'Deutsch';
                    break; /* German */
                case 'el_GR':
                    $name = 'Greek';
                    break; /* Greek */
                case 'en_GB':
                    $name = 'English (UK)';
                    break; /* English */
                case 'en_US':
                    $name = 'English (US)';
                    break; /* English */
                case 'es_AR':
                    $name = 'Espa&#241;ol (AR)';
                    break; /* Spanish */
                case 'es_ES':
                    $name = 'Espa&#241;ol';
                    break; /* Spanish */
                case 'es_MX':
                    $name = 'Espa&#241;ol (MX)';
                    break; /* Spanish */
                case 'et_EE':
                    $name = 'Eesti';
                    break; /* Estonian */
                case 'eu_ES':
                    $name = 'Euskara';
                    break; /* Basque */
                case 'fi_FI':
                    $name = 'Suomi';
                    break; /* Finnish */
                case 'fr_FR':
                    $name = 'Fran&#231;ais';
                    break; /* French */
                case 'ga_IE':
                    $name = 'Gaeilge';
                    break; /* Irish */
                case 'hu_HU':
                    $name = 'Magyar';
                    break; /* Hungarian */
                case 'id_ID':
                    $name = 'Indonesia';
                    break; /* Indonesian */
                case 'is_IS':
                    $name = 'Icelandic';
                    break; /* Icelandic */
                case 'it_IT':
                    $name = 'Italiano';
                    break; /* Italian */
                case 'ja_JP':
                    $name = '&#x65e5;&#x672c;&#x8a9e;';
                    break; /* Japanese */
                case 'ko_KR':
                    $name = '&#xd55c;&#xad6d;&#xb9d0;';
                    break; /* Korean */
                case 'lt_LT':
                    $name = 'Lietuvi&#371;';
                    break; /* Lithuanian */
                case 'lv_LV':
                    $name = 'Latvie&#353;u';
                    break; /* Latvian */
                case 'nb_NO':
                    $name = 'Norsk';
                    break; /* Norwegian */
                case 'nl_NL':
                    $name = 'Nederlands';
                    break; /* Dutch */
                case 'no_NO':
                    $name = 'Norsk bokm&#229;l';
                    break; /* Norwegian */
                case 'pl_PL':
                    $name = 'Polski';
                    break; /* Polish */
                case 'pt_BR':
                    $name = 'Portugu&#234;s Brasileiro';
                    break; /* Portuguese */
                case 'pt_PT':
                    $name = 'Portugu&#234;s';
                    break; /* Portuguese */
                case 'ro_RO':
                    $name = 'Rom&#226;n&#259;';
                    break; /* Romanian */
                case 'ru_RU':
                    $name = '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;';
                    break; /* Russian */
                case 'sk_SK':
                    $name = 'Sloven&#269;ina';
                    break; /* Slovak */
                case 'sl_SI':
                    $name = 'Sloven&#353;&#269;ina';
                    break; /* Slovenian */
                case 'sr_CS':
                    $name = 'Srpski';
                    break; /* Serbian */
                case 'sv_SE':
                    $name = 'Svenska';
                    break; /* Swedish */
                case 'tr_TR':
                    $name = 'T&#252;rk&#231;e';
                    break; /* Turkish */
                case 'uk_UA':
                    $name = 'Українська';
                    break; /* Ukrainian */
                case 'vi_VN':
                    $name = 'Ti&#7871;ng Vi&#7879;t';
                    break; /* Vietnamese */
                case 'zh_CN':
                    $name = '&#31616;&#20307;&#20013;&#25991;';
                    break; /* Chinese (simplified)*/
                case 'zh_TW':
                    $name = '&#32321;&#39636;&#20013;&#25991;';
                    break; /* Chinese (traditional)*/
                /* These languages are right to left. */
                case 'ar_SA':
                    $name = '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;';
                    break; /* Arabic */
                case 'he_IL':
                    $name = '&#1506;&#1489;&#1512;&#1497;&#1514;';
                    break; /* Hebrew */
                case 'fa_IR':
                    $name = '&#1601;&#1575;&#1585;&#1587;&#1610;';
                    break; /* Farsi */
                default:
                    $name = sprintf(
                        /* HINT: File */
                        T_('Unknown %s'), '(' . $file . ')');
                    break;
            } // end switch

            $results[$file] = $name;
        }
    } // end while

    // Sort the list of languages by country code
    ksort($results);

    // Prepend English (US)
    $results = array("en_US" => "English (US)") + $results;

    return $results;
} // get_languages

/**
 * is_rtl
 * This checks whether to be a Right-To-Left language.
 * @param $locale
 * @return boolean
 */
function is_rtl($locale)
{
    return in_array($locale, array("he_IL", "fa_IR", "ar_SA"));
}

/**
 * translate_pattern_code
 * This just contains a keyed array which it checks against to give you the
 * 'tag' name that said pattern code corresponds to. It returns false if nothing
 * is found.
 * @param $code
 * @return string|false
 */
function translate_pattern_code($code)
{
    $code_array = array('%A' => 'album',
            '%a' => 'artist',
            '%c' => 'comment',
            '%C' => 'catalog_number',
            '%T' => 'track',
            '%d' => 'disk',
            '%g' => 'genre',
            '%t' => 'title',
            '%y' => 'year',
            '%Y' => 'original_year',
            '%r' => 'release_type',
            '%b' => 'barcode',
            '%o' => 'zz_other');

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
 * @param $current
 * @return string
 * @throws Exception
 */
function generate_config($current)
{
    // Start building the new config file
    $distfile = AmpConfig::get('prefix') . '/config/ampache.cfg.php.dist';
    $handle   = fopen($distfile, 'r');
    $dist     = fread($handle, filesize($distfile));
    fclose($handle);

    $data = explode("\n", (string) $dist);

    $final = "";
    foreach ($data as $line) {
        if (preg_match("/^;?([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/", $line, $matches)
            || preg_match("/^;?([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $line, $matches)
            || preg_match("/^;?([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/", $line, $matches)) {
            $key   = $matches[1];
            $value = $matches[2];

            // Put in the current value
            if ($key == 'config_version') {
                $line = $key . ' = ' . escape_ini($value);
            } elseif ($key == 'secret_key' && !isset($current[$key])) {
                $secret_key = Core::gen_secure_token(31);
                if ($secret_key !== false) {
                    $line = $key . ' = "' . escape_ini($secret_key) . '"';
                }
                // Else, unable to generate a cryptographically secure token, use the default one
            } elseif (isset($current[$key])) {
                $line = $key . ' = "' . escape_ini((string) $current[$key]) . '"';
                unset($current[$key]);
            }
        }

        $final .= $line . "\n";
    }

    return $final;
}

/**
 * write_config
 *
 * Write new configuration into the current configuration file by keeping old values.
 * @param $current_file_path
 * @throws Exception
 */
function write_config($current_file_path)
{
    $new_data = generate_config(parse_ini_file($current_file_path));

    // Start writing into the current config file
    $handle = fopen($current_file_path, 'w+');
    fwrite($handle, $new_data, strlen((string) $new_data));
    fclose($handle);
}

/**
 * escape_ini
 *
 * Escape a value used for inserting into an ini file.
 * Won't quote ', like addslashes does.
 * @param string $str
 * @return string|string[]
 */
function escape_ini($str)
{
    return str_replace('"', '\"', $str);
}

// Declare apache_request_headers and getallheaders if it don't exists (PHP <= 5.3 + FastCGI)
if (!function_exists('apache_request_headers')) {
    /**
     * @return array
     */
    function apache_request_headers()
    {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } else {
                if ($name == "CONTENT_TYPE") {
                    $headers["Content-Type"] = $value;
                } else {
                    if ($name == "CONTENT_LENGTH") {
                        $headers["Content-Length"] = $value;
                    }
                }
            }
        }

        return $headers;
    }

    /**
     * @return array
     */
    function getallheaders()
    {
        return apache_request_headers();
    }
}

/**
 * @return string
 */
function get_current_path()
{
    if (strlen((string) $_SERVER['PHP_SELF'])) {
        $root = $_SERVER['PHP_SELF'];
    } else {
        $root = $_SERVER['REQUEST_URI'];
    }

    return (string) $root;
}

/**
 * @return string
 */
function get_web_path()
{
    $root = get_current_path();

    return (string) preg_replace('#(.*)/(\w+\.php)$#', '$1', $root);
}

/**
 * get_datetime
 * @param string $date
 * @param integer $time
 * @return string
 */
function get_datetime($date, $time)
{
    if (empty($date)) {
        $date = 'm/d/Y H:i';
    }

    return date($date, $time);
}
