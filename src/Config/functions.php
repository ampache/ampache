<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Metadata\Repository\MetadataField;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\TVShow_Season;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Localplay\LocalPlayTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Ui;
use Gettext\Translator;
use Psr\Log\LoggerInterface;

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

    $current_limit = Ui::unformat_bytes($current_limit);
    $new_limit     = Ui::unformat_bytes($new_limit);

    if ($current_limit < $new_limit) {
        ini_set('memory_limit', $new_limit);
    }
} // set_memory_limit

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
 *
 * @deprecated see Ui::scrubOut
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
    $handle = opendir(__DIR__ . '/../../locale');

    if (!is_resource($handle)) {
        debug_event('general.lib', 'Error unable to open locale directory', 1);
    }

    $results = array();

    while (false !== ($file = readdir($handle))) {
        $full_file = __DIR__ . '/../../locale/' . $file;

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
}
if (!function_exists('getallheaders')) {
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
 * @param integer $time
 * @param string $date_format
 * @param string $time_format
 * @param string $overwrite
 * @return string
 */
function get_datetime($time, $date_format = 'short', $time_format = 'short', $overwrite = '')
{
    // allow time or date only
    $date_type = ($date_format == 'none') ? IntlDateFormatter::NONE : IntlDateFormatter::SHORT;
    $time_type = ($time_format == 'none') ? IntlDateFormatter::NONE : IntlDateFormatter::SHORT;
    // if no override is set but you have a custom_datetime
    $pattern = ($overwrite == '') ? (string) AmpConfig::get('custom_datetime', '') : $overwrite;

    // get your locale and set the date based on that, unless you have 'custom_datetime set'
    $locale = AmpConfig::get('lang', 'en_US');
    $format = new IntlDateFormatter($locale, $date_type, $time_type, null, null, $pattern);

    return $format->format($time);
}

/**
 * check_config_values
 * checks to make sure that they have at least set the needed variables
 * @param array $conf
 * @return boolean
 */
function check_config_values($conf)
{
    if (!$conf['database_hostname']) {
        return false;
    }
    if (!$conf['database_name']) {
        return false;
    }
    if (!$conf['database_username']) {
        return false;
    }
    /* Don't check for password to support mysql socket auth
     * if (!$conf['database_password']) {
        return false;
    }*/
    if (!$conf['session_length']) {
        return false;
    }
    if (!$conf['session_name']) {
        return false;
    }
    if (!isset($conf['session_cookielife'])) {
        return false;
    }
    if (!isset($conf['session_cookiesecure'])) {
        return false;
    }
    if (isset($conf['debug'])) {
        if (!isset($conf['log_path'])) {
            return false;
        }
    }

    return true;
} // check_config_values

/**
 * @param string $val
 * @return integer|string
 */
function return_bytes($val)
{
    $val  = trim((string) $val);
    $last = strtolower((string) $val[strlen((string) $val) - 1]);
    switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
            // Intentional break fall-through
        case 'm':
            $val *= 1024;
            // Intentional break fall-through
        case 'k':
            $val *= 1024;
            break;
    }

    return $val;
}

/**
 * check_config_writable
 * This checks whether we can write the config file
 * @return boolean
 */
function check_config_writable()
{
    // file eixsts && is writable, or dir is writable
    return ((file_exists(__DIR__ . '/../../config/ampache.cfg.php') && is_writable(__DIR__ . '/../../config/ampache.cfg.php'))
        || (!file_exists(__DIR__ . '/../../config/ampache.cfg.php') && is_writeable(__DIR__ . '/../../config/')));
}

/**
 * @return boolean
 */
function check_htaccess_channel_writable()
{
    return ((file_exists(__DIR__ . '/../../public/channel/.htaccess') && is_writable(__DIR__ . '/../../public/channel/.htaccess'))
        || (!file_exists(__DIR__ . '/../../public/channel/.htaccess') && is_writeable(__DIR__ . '/../../public/channel/')));
}

/**
 * @return boolean
 */
function check_htaccess_rest_writable()
{
    return ((file_exists(__DIR__ . '/../../public/rest/.htaccess') && is_writable(__DIR__ . '/../../public/rest/.htaccess'))
        || (!file_exists(__DIR__ . '/../../public/rest/.htaccess') && is_writeable(__DIR__ . '/../../public/rest/')));
}

/**
 * @return boolean
 */
function check_htaccess_play_writable()
{
    return ((file_exists(__DIR__ . '/../../public/play/.htaccess') && is_writable(__DIR__ . '/../../public/play/.htaccess'))
        || (!file_exists(__DIR__ . '/../../public/play/.htaccess') && is_writeable(__DIR__ . '/../../public/play/')));
}

/**
 * debug_result
 * Convenience function to format the output.
 * @param string|boolean $status
 * @param string $value
 * @param string $comment
 * @return string
 */
function debug_result($status = false, $value = null, $comment = '')
{
    $class = $status ? 'success' : 'danger';

    if ($value === null) {
        $value = $status ? T_('OK') : T_('Error');
    }

    return '<button type="button" class="btn btn-' . $class . '">' . scrub_out($value) .
        '</span> <em>' . $comment . '</em></button>';
}

/**
 * ampache_error_handler
 *
 * An error handler for ampache that traps as many errors as it can and logs
 * them.
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 */
function ampache_error_handler($errno, $errstr, $errfile, $errline)
{
    $level = 1;

    switch ($errno) {
        case E_WARNING:
            $error_name = 'Runtime Error';
            break;
        case E_COMPILE_WARNING:
        case E_NOTICE:
        case E_CORE_WARNING:
            $error_name = 'Warning';
            $level      = 6;
            break;
        case E_ERROR:
            $error_name = 'Fatal run-time Error';
            break;
        case E_PARSE:
            $error_name = 'Parse Error';
            break;
        case E_CORE_ERROR:
            $error_name = 'Fatal Core Error';
            break;
        case E_COMPILE_ERROR:
            $error_name = 'Zend run-time Error';
            break;
        case E_STRICT:
            $error_name = "Strict Error";
            break;
        default:
            $error_name = "Error";
            $level      = 2;
            break;
    } // end switch

    // List of things that should only be displayed if they told us to turn
    // on the firehose
    $ignores = array(
        // We know var is deprecated, shut up
        'var: Deprecated. Please use the public/private/protected modifiers',
        // getid3 spews errors, yay!
        'getimagesize() [',
        'Non-static method getid3',
        'Assigning the return value of new by reference is deprecated',
        // The XML-RPC lib is broken (kinda)
        'used as offset, casting to integer'
    );

    foreach ($ignores as $ignore) {
        if (strpos($errstr, $ignore) !== false) {
            $error_name = 'Ignored ' . $error_name;
            $level      = 7;
        }
    }

    if (error_reporting() == 0) {
        // Ignored, probably via @. But not really, so use the super-sekrit level
        $level = 7;
    }

    if (strpos($errstr, 'date.timezone') !== false) {
        $error_name = 'Warning';
        $errstr     = 'You have not set a valid timezone (date.timezone) in your php.ini file. This may cause display issues with dates. This warning is non-critical and not caused by Ampache.';
    }

    $log_line = "[$error_name] $errstr in file $errfile($errline)";
    debug_event('log.lib', $log_line, $level, '', 'ampache');
}

/**
 * debug_event
 * This function is called inside Ampache, it's actually a wrapper for the
 * log_event. It checks config for debug and debug_level and only
 * calls log event if both requirements are met.
 * @param string $type
 * @param string $message
 * @param integer $level
 * @param string $file
 * @param string $username
 * @return boolean
 *
 * @deprecated Use LegacyLogger
 */
function debug_event($type, $message, $level, $file = '', $username = '')
{
    if (!$username && Core::get_global('user')) {
        $username = Core::get_global('user')->username;
    }

    global $dic;
    $logger = $dic->get(LoggerInterface::class);

    // If the message is multiple lines, make multiple log lines
    foreach (explode("\n", (string) $message) as $line) {
        $logger->log(
            $level,
            $line,
            [
                'username' => $username,
                'event_type' => $type
            ]
        );
    }

    return true;
} // debug_event

/**
 * @param $action
 * @param $catalogs
 * @param array $options
 */
function catalog_worker($action, $catalogs = null, $options = null)
{
    if (AmpConfig::get('ajax_load')) {
        $sse_url = AmpConfig::get('web_path') . "/server/sse.server.php?worker=catalog&action=" . $action . "&catalogs=" . urlencode(json_encode($catalogs));
        if ($options) {
            $sse_url .= "&options=" . urlencode(json_encode($_POST));
        }
        sse_worker($sse_url);
    } else {
        Catalog::process_action($action, $catalogs, $options);
    }
}

/**
 * @param string $url
 */
function sse_worker($url)
{
    echo '<script>';
    echo "sse_worker('$url');";
    echo "</script>\n";
}

/**
 * return_referer
 * returns the script part of the referer address passed by the web browser
 * this is not %100 accurate. Also because this is not passed by us we need
 * to clean it up, take the filename then check for a /admin/ and dump the rest
 * @return string
 */
function return_referer()
{
    $referer = $_SERVER['HTTP_REFERER'];
    if (substr($referer, -1) == '/') {
        $file = 'index.php';
    } else {
        $file = basename($referer);
        /* Strip off the filename */
        $referer = substr($referer, 0, strlen((string) $referer) - strlen((string) $file));
    }

    if (substr($referer, strlen((string) $referer) - 6, 6) == 'admin/') {
        $file = 'admin/' . $file;
    }

    return $file;
} // return_referer

/**
 * get_location
 * This function gets the information about a person's current location.
 * This is used for A) sidebar highlighting & submenu showing and B) titlebar
 * information. It returns an array of information about what they are currently
 * doing.
 * Possible array elements
 * ['title']    Text name for the page
 * ['page']    actual page name
 * ['section']    name of the section we are in, admin, browse etc (submenu)
 */
function get_location()
{
    $location = array();

    if (strlen((string) $_SERVER['PHP_SELF'])) {
        $source = $_SERVER['PHP_SELF'];
    } else {
        $source = $_SERVER['REQUEST_URI'];
    }

    /* Sanatize the $_SERVER['PHP_SELF'] variable */
    $source           = str_replace(AmpConfig::get('raw_web_path'), "", $source);
    $location['page'] = preg_replace("/^\/(.+\.php)\/?.*/", "$1", $source);

    switch ($location['page']) {
        case 'index.php':
            $location['title'] = T_('Home');
            break;
        case 'upload.php':
            $location['title'] = T_('Upload');
            break;
        case 'localplay.php':
            $location['title'] = T_('Localplay');
            break;
        case 'randomplay.php':
            $location['title'] = T_('Random Play');
            break;
        case 'playlist.php':
            $location['title'] = T_('Playlist');
            break;
        case 'search.php':
            $location['title'] = T_('Search');
            break;
        case 'preferences.php':
            $location['title'] = T_('Preferences');
            break;
        case 'admin/catalog.php':
        case 'admin/index.php':
            $location['title']   = T_('Admin-Catalog');
            $location['section'] = 'admin';
            break;
        case 'admin/users.php':
            $location['title']   = T_('Admin-User Management');
            $location['section'] = 'admin';
            break;
        case 'admin/mail.php':
            $location['title']   = T_('Admin-Mail Users');
            $location['section'] = 'admin';
            break;
        case 'admin/access.php':
            $location['title']   = T_('Admin-Manage Access Lists');
            $location['section'] = 'admin';
            break;
        case 'admin/preferences.php':
            $location['title']   = T_('Admin-Site Preferences');
            $location['section'] = 'admin';
            break;
        case 'admin/modules.php':
            $location['title']   = T_('Admin-Manage Modules');
            $location['section'] = 'admin';
            break;
        case 'browse.php':
            $location['title']   = T_('Browse Music');
            $location['section'] = 'browse';
            break;
        case 'albums.php':
            $location['title']   = T_('Albums');
            $location['section'] = 'browse';
            break;
        case 'artists.php':
            $location['title']   = T_('Artists');
            $location['section'] = 'browse';
            break;
        case 'stats.php':
            $location['title'] = T_('Statistics');
            break;
        default:
            $location['title'] = '';
            break;
    } // switch on raw page location

    return $location;
} // get_location

/**
 * show_album_select
 * This displays a select of every album that we've got in Ampache (which can be
 * hella long). It's used by the Edit page and takes a $name and a $album_id
 * @param string $name
 * @param integer $album_id
 * @param boolean $allow_add
 * @param integer $song_id
 * @param boolean $allow_none
 * @param string $user
 */
function show_album_select($name, $album_id = 0, $allow_add = false, $song_id = 0, $allow_none = false, $user = null)
{
    static $album_id_cnt = 0;

    // Generate key to use for HTML element ID
    if ($song_id) {
        $key = "album_select_" . $song_id;
    } else {
        $key = "album_select_c" . ++$album_id_cnt;
    }

    $sql    = "SELECT `album`.`id`, `album`.`name`, `album`.`prefix`, `disk` FROM `album`";
    $params = array();
    if ($user !== null) {
        $sql .= "INNER JOIN `artist` ON `artist`.`id` = `album`.`album_artist` WHERE `album`.`album_artist` IS NOT NULL AND `artist`.`user` = ? ";
        $params[] = $user;
    }
    $sql .= "ORDER BY `album`.`name`";
    $db_results = Dba::read($sql, $params);
    $count      = Dba::num_rows($db_results);

    // Added ID field so we can easily observe this element
    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected   = '';
        $album_name = trim((string) $row['prefix'] . " " . $row['name']);
        if (!AmpConfig::get('album_group') && (int) $count > 1) {
            $album_name .= " [" . T_('Disk') . " " . $row['disk'] . "]";
        }
        if ($row['id'] == $album_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($album_name) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_('Add New') . "...</option>\n";
    }

    echo "</select>\n";

    if ($count === 0) {
        echo "<script>check_inline_song_edit('" . $name . "', " . $song_id . ");</script>\n";
    }
} // show_album_select

/**
 * show_artist_select
 * This is the same as show_album_select except it's *gasp* for artists! How
 * inventive!
 * @param string $name
 * @param integer $artist_id
 * @param boolean $allow_add
 * @param integer $song_id
 * @param boolean $allow_none
 * @param integer $user_id
 */
function show_artist_select($name, $artist_id = 0, $allow_add = false, $song_id = 0, $allow_none = false, $user_id = null)
{
    static $artist_id_cnt = 0;
    // Generate key to use for HTML element ID
    if ($song_id) {
        $key = $name . "_select_" . $song_id;
    } else {
        $key = $name . "_select_c" . ++$artist_id_cnt;
    }

    $sql    = "SELECT `id`, `name`, `prefix` FROM `artist` ";
    $params = array();
    if ($user_id !== null) {
        $sql .= "WHERE `user` = ? ";
        $params[] = $user_id;
    }
    $sql .= "ORDER BY `name`";
    $db_results = Dba::read($sql, $params);
    $count      = Dba::num_rows($db_results);

    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected    = '';
        $artist_name = trim((string) $row['prefix'] . " " . $row['name']);
        if ($row['id'] == $artist_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($artist_name) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_('Add New') . "...</option>\n";
    }

    echo "</select>\n";

    if ($count === 0) {
        echo "<script>check_inline_song_edit('" . $name . "', " . $song_id . ");</script>\n";
    }
} // show_artist_select

/**
 * show_tvshow_select
 * This is the same as show_album_select except it's *gasp* for tvshows! How
 * inventive!
 * @param string $name
 * @param integer $tvshow_id
 * @param boolean $allow_add
 * @param integer $season_id
 * @param boolean $allow_none
 */
function show_tvshow_select($name, $tvshow_id = 0, $allow_add = false, $season_id = 0, $allow_none = false)
{
    static $tvshow_id_cnt = 0;
    // Generate key to use for HTML element ID
    if ($season_id) {
        $key = $name . "_select_" . $season_id;
    } else {
        $key = $name . "_select_c" . ++$tvshow_id_cnt;
    }

    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    $sql        = "SELECT `id`, `name` FROM `tvshow` ORDER BY `name`";
    $db_results = Dba::read($sql);

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == $tvshow_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['name']) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_('Add New') . "...</option>\n";
    }

    echo "</select>\n";
} // show_tvshow_select

/**
 * @param string $name
 * @param $season_id
 * @param boolean $allow_add
 * @param integer $video_id
 * @param boolean $allow_none
 * @return boolean
 */
function show_tvshow_season_select($name, $season_id, $allow_add = false, $video_id = 0, $allow_none = false)
{
    if (!$season_id) {
        return false;
    }
    $season = new TVShow_Season($season_id);

    static $season_id_cnt = 0;
    // Generate key to use for HTML element ID
    if ($video_id) {
        $key = $name . "_select_" . $video_id;
    } else {
        $key = $name . "_select_c" . ++$season_id_cnt;
    }

    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    $sql        = "SELECT `id`, `season_number` FROM `tvshow_season` WHERE `tvshow` = ? ORDER BY `season_number`";
    $db_results = Dba::read($sql, array($season->tvshow));

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == $season_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['season_number']) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_('Add New') . "...</option>\n";
    }

    echo "</select>\n";

    return true;
}

/**
 * show_catalog_select
 * Yet another one of these buggers. this shows a drop down of all of your
 * catalogs.
 * @param string $name
 * @param integer $catalog_id
 * @param string $style
 * @param boolean $allow_none
 * @param string $filter_type
 */
function show_catalog_select($name, $catalog_id, $style = '', $allow_none = false, $filter_type = '')
{
    echo "<select name=\"$name\" style=\"$style\">\n";

    $params = array();
    $sql    = "SELECT `id`, `name` FROM `catalog` ";
    if (!empty($filter_type)) {
        $sql .= "WHERE `gather_types` = ?";
        $params[] = $filter_type;
    }
    $sql .= "ORDER BY `name`";
    $db_results = Dba::read($sql, $params);

    if ($allow_none) {
        echo "\t<option value=\"-1\">" . T_('None') . "</option>\n";
    }

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == (string) $catalog_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['name']) . "</option>\n";
    } // end while

    echo "</select>\n";
} // show_catalog_select

/**
 * show_album_select
 * This displays a select of every album that we've got in Ampache (which can be
 * hella long). It's used by the Edit page and takes a $name and a $album_id
 * @param string $name
 * @param integer $license_id
 * @param integer $song_id
 */
function show_license_select($name, $license_id = 0, $song_id = 0)
{
    static $license_id_cnt = 0;

    // Generate key to use for HTML element ID
    if ($song_id > 0) {
        $key = "license_select_" . $song_id;
    } else {
        $key = "license_select_c" . ++$license_id_cnt;
    }

    // Added ID field so we can easily observe this element
    echo "<select name=\"$name\" id=\"$key\">\n";

    $sql        = "SELECT `id`, `name`, `description`, `external_link` FROM `license` ORDER BY `name`";
    $db_results = Dba::read($sql);

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == $license_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected";
        if (!empty($row['description'])) {
            echo " title=\"" . addslashes($row['description']) . "\"";
        }
        if (!empty($row['external_link'])) {
            echo " data-link=\"" . $row['external_link'] . "\"";
        }
        echo ">" . $row['name'] . "</option>\n";
    } // end while

    echo "</select>\n";
    echo "<a href=\"javascript:show_selected_license_link('" . $key . "');\">" . T_('View License') . "</a>";
} // show_license_select

/**
 * show_user_select
 * This one is for users! shows a select/option statement so you can pick a user
 * to blame
 * @param string $name
 * @param string $selected
 * @param string $style
 */
function show_user_select($name, $selected = '', $style = '')
{
    echo "<select name=\"$name\" style=\"$style\">\n";
    echo "\t<option value=\"\">" . T_('All') . "</option>\n";

    $sql        = "SELECT `id`, `username`, `fullname` FROM `user` ORDER BY `fullname`";
    $db_results = Dba::read($sql);

    while ($row = Dba::fetch_assoc($db_results)) {
        $select_txt = '';
        if ($row['id'] == $selected) {
            $select_txt = 'selected="selected"';
        }
        // If they don't have a full name, revert to the username
        $row['fullname'] = $row['fullname'] ? $row['fullname'] : $row['username'];

        echo "\t<option value=\"" . $row['id'] . "\" $select_txt>" . scrub_out($row['fullname']) . "</option>\n";
    } // end while users

    echo "</select>\n";
} // show_user_select


function xoutput_headers()
{
    $output = (Core::get_request('xoutput') !== '') ? Core::get_request('xoutput') : 'xml';
    if ($output == 'xml') {
        header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'));
        header("Content-Disposition: attachment; filename=ajax.xml");
    } else {
        header("Content-type: application/json; charset=" . AmpConfig::get('site_charset'));
    }

    header("Expires: Tuesday, 27 Mar 1984 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
}

/**
 * @param array $array
 * @param boolean $callback
 * @param string $type
 * @return false|mixed|string
 */
function xoutput_from_array($array, $callback = false, $type = '')
{
    $output = (Core::get_request('xoutput') !== '') ? Core::get_request('xoutput') : 'xml';
    if ($output == 'xml') {
        return Xml_Data::output_xml_from_array($array, $callback, $type);
    } elseif ($output == 'raw') {
        $outputnode = Core::get_request('xoutputnode');

        return $array[$outputnode];
    } else {
        return json_encode($array);
    }
}

/**
 * toggle_visible
 * This is identical to the javascript command that it actually calls
 * @param $element
 */
function toggle_visible($element)
{
    echo '<script>';
    echo "toggleVisible('$element');";
    echo "</script>\n";
} // toggle_visible

/**
 * display_notification
 * Show a javascript notification to the user
 * @param string $message
 * @param integer $timeout
 */
function display_notification($message, $timeout = 5000)
{
    echo "<script>";
    echo "displayNotification('" . addslashes(json_encode($message, JSON_UNESCAPED_UNICODE)) . "', " . $timeout . ");";
    echo "</script>\n";
}

/**
 * print_bool
 * This function takes a boolean value and then prints out a friendly text
 * message.
 * @param $value
 * @return string
 */
function print_bool($value)
{
    if ($value) {
        $string = '<span class="item_on">' . T_('On') . '</span>';
    } else {
        $string = '<span class="item_off">' . T_('Off') . '</span>';
    }

    return $string;
} // print_bool

/**
 * show_now_playing
 * This shows the Now Playing templates and does some garbage collection
 * this should really be somewhere else
 */
function show_now_playing()
{
    Session::garbage_collection();
    Stream::garbage_collection();

    $web_path = AmpConfig::get('web_path');
    $results  = Stream::get_now_playing();
    require_once Ui::find_template('show_now_playing.inc.php');
} // show_now_playing

/**
 * @param boolean $render
 * @param boolean $force
 */
function show_table_render($render = false, $force = false)
{
    // Include table render javascript only once
    if ($force || !defined('TABLE_RENDERED')) {
        define('TABLE_RENDERED', 1); ?>
        <?php if (isset($render) && $render) { ?>
            <script>sortPlaylistRender();</script>
            <?php
        }
    }
}

/**
 * load_gettext
 * Sets up our local gettext settings.
 *
 * @return boolean
 */
function load_gettext()
{
    $lang   = AmpConfig::get('lang');
    $mopath = __DIR__ . '/../../locale/' . $lang . '/LC_MESSAGES/messages.mo';

    $gettext = new Translator();
    if (file_exists($mopath)) {
        $translations = Gettext\Translations::fromMoFile($mopath);
        $gettext->loadTranslations($translations);
    }
    $gettext->register();

    return true;
} // load_gettext

/**
 * T_
 * Translate string
 * @param string $msgid
 * @return string
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

/**
 * get_themes
 * this looks in /themes and pulls all of the
 * theme.cfg.php files it can find and returns an
 * array of the results
 * @return array
 */
function get_themes()
{
    /* Open the themes dir and start reading it */
    $handle = opendir(__DIR__ . '/../../public/themes');

    if (!is_resource($handle)) {
        debug_event('themes', 'Failed to open /themes directory', 2);

        return array();
    }

    $results = array();
    while (($file = readdir($handle)) !== false) {
        if ((string) $file !== '.' && (string) $file !== '..') {
            debug_event('themes', "Checking $file", 5);
            $cfg = get_theme($file);
            if ($cfg !== null) {
                $results[$cfg['name']] = $cfg;
            }
        }
    } // end while directory
    // Sort by the theme name
    ksort($results);

    return $results;
} // get_themes

/**
 * @function get_theme
 * @discussion get a single theme and read the config file
 * then return the results
 * @param string $name
 * @return array|boolean|false|mixed|null
 */
function get_theme($name)
{
    static $_mapcache = array();

    if (strlen((string) $name) < 1) {
        return false;
    }

    $name = strtolower((string) $name);

    if (isset($_mapcache[$name])) {
        return $_mapcache[$name];
    }

    $config_file = __DIR__ . "/../../public/themes/" . $name . "/theme.cfg.php";
    if (file_exists($config_file)) {
        $results         = parse_ini_file($config_file);
        $results['path'] = $name;
        $results['base'] = explode(',', (string) $results['base']);
        $nbbases         = count($results['base']);
        for ($count = 0; $count < $nbbases; $count++) {
            $results['base'][$count] = explode('|', $results['base'][$count]);
        }
        $results['colors'] = explode(',', (string) $results['colors']);
    } else {
        $results = null;
    }
    $_mapcache[$name] = $results;

    return $results;
} // get_theme

/**
 * @function get_theme_author
 * @discussion returns the author of this theme
 * @param string $theme_name
 * @return string
 */
function get_theme_author($theme_name)
{
    $theme_path = __DIR__ . '/../../public/themes/' . $theme_name . '/theme.cfg.php';
    $results    = read_config($theme_path);

    return $results['author'];
} // get_theme_author

/**
 * @function theme_exists
 * @discussion this function checks to make sure that a theme actually exists
 * @param string $theme_name
 * @return boolean
 */
function theme_exists($theme_name)
{
    $theme_path = __DIR__ . '/../../public/themes/' . $theme_name . '/theme.cfg.php';

    if (!file_exists($theme_path)) {
        return false;
    }

    return true;
} // theme_exists

/**
 * Used in graph class als format string
 *
 * @see \Ampache\Module\Util\Graph
 *
 * @param $value
 * @return string
 */
function pGraph_Yformat_bytes($value)
{
    return Ui::format_bytes($value);
}

function get_mime_from_image($data): string
{
    switch ($data) {
        case substr($data, 0, 4) == 'ffd8':
            return "image/jpeg";
        case '89504E47':
            return "image/png";
        case '47494638':
            return "image/gif";
        case substr($data,0, 4) == '424d':
            return 'image/bmp';
        default:
            return 'image/jpeg';
    }
}
    
/**
 * @deprecated Will be removed
 */
function canEditArtist(
    Artist $artist,
    int $userId
): bool {
    if (AmpConfig::get('upload_allow_edit')) {
        if ($artist->user !== null && $userId == $artist->user) {
            return true;
        }
    }

    global $dic;

    return $dic->get(PrivilegeCheckerInterface::class)->check(
        AccessLevelEnum::TYPE_INTERFACE,
        AccessLevelEnum::LEVEL_CONTENT_MANAGER
    );
}
