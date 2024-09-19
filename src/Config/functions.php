<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Ui;
use Gettext\Loader\MoLoader;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Psr\Log\LoggerInterface;

/**
 * set_memory_limit
 * This function attempts to change the php memory limit using init_set.
 * Will never reduce it below the current setting.
 * @param int|string $new_limit
 */
function set_memory_limit($new_limit): void
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
}

/**
 * scrub_in
 * Run on inputs, stuff that might get stuck in our db
 *
 * @template TType of string|array
 *
 * @param TType $input
 * @return TType
 */
function scrub_in($input)
{
    if (!is_array($input)) {
        return stripslashes(htmlspecialchars(strip_tags((string) $input), ENT_NOQUOTES, AmpConfig::get('site_charset')));
    } else {
        $results = [];
        foreach ($input as $item) {
            $results[] = scrub_in((string) $item);
        }

        return $results;
    }
}

/**
 * scrub_out
 * This function is used to escape user data that is getting redisplayed
 * onto the page, it htmlentities the mojo
 * This is the inverse of the scrub_in function
 * (Not deprecated yet see Ui::scrubOut)
 * @param null|string $string
 *
 */
function scrub_out($string): string
{
    if ($string === null) {
        return '';
    }

    return htmlentities((string) $string, ENT_QUOTES, AmpConfig::get('site_charset'));
}

/**
 * unhtmlentities
 * Undoes htmlentities()
 * @param string $string
 */
function unhtmlentities($string): string
{
    return html_entity_decode((string) $string, ENT_QUOTES, AmpConfig::get('site_charset'));
}

/**
 * make_bool
 * This takes a value and returns what we consider to be the correct boolean
 * value. We need a special function because PHP considers "false" to be true.
 *
 * @param bool|null|string $string
 */
function make_bool($string): bool
{
    if (is_bool($string)) {
        return $string;
    }
    if ($string === null) {
        return false;
    }
    if (strcasecmp((string) $string, 'false') == 0 || $string == '0') {
        return false;
    }

    return (bool) $string;
}

/**
 * invert_bool
 * This returns the opposite of what you've got
 * @param bool|string $value
 */
function invert_bool($value): bool
{
    return make_bool($value) ? false : true;
}

/**
 * get_languages
 * This function does a dir of ./locale and pulls the names of the
 * different languages installed, this means that all you have to do
 * is drop one in and it will show up on the context menu. It returns
 * in the form of an array of names
 * @return array
 */
function get_languages(): array
{
    /* Open the locale directory */
    $handle = opendir(__DIR__ . '/../../locale');

    if (!is_resource($handle)) {
        debug_event('general.lib', 'Error unable to open locale directory', 1);

        return ["en_US" => "English (US)"];
    }

    $results = [];

    while (false !== ($file = readdir($handle))) {
        $full_file = __DIR__ . '/../../locale/' . $file;

        // Check to see if it's a directory
        if (
            is_dir($full_file) &&
            substr($file, 0, 1) != '.' &&
            $file != 'base'
        ) {
            $name = match ($file) {
                'af_ZA' => 'Afrikaans',
                'bg_BG' => '&#x0411;&#x044a;&#x043b;&#x0433;&#x0430;&#x0440;&#x0441;&#x043a;&#x0438;',
                'ca_ES' => 'Catal&#224;',
                'cs_CZ' => '&#x010c;esky',
                'da_DK' => 'Dansk',
                'de_CH' => 'Deutschschweiz',
                'de_DE' => 'Deutsch',
                'el_GR' => 'Greek',
                'en_AU' => 'English (AU)',
                'en_GB' => 'English (UK)',
                'en_US' => 'English (US)',
                'es_AR' => 'Espa&#241;ol (AR)',
                'es_ES' => 'Espa&#241;ol',
                'es_MX' => 'Espa&#241;ol (MX)',
                'et_EE' => 'Eesti',
                'eu_ES' => 'Euskara',
                'fi_FI' => 'Suomi',
                'fr_BE' => 'Fran&#231;ais de Belgique ',
                'fr_FR' => 'Fran&#231;ais',
                'ga_IE' => 'Gaeilge',
                'gl_ES' => 'Galician',
                'hi_IN' => 'Hindi',
                'hu_HU' => 'Magyar',
                'id_ID' => 'Indonesia',
                'is_IS' => 'Icelandic',
                'it_IT' => 'Italiano',
                'ja_JP' => '&#x65e5;&#x672c;&#x8a9e;',
                'ko_KR' => '&#xd55c;&#xad6d;&#xb9d0;',
                'lt_LT' => 'Lietuvi&#371;',
                'lv_LV' => 'Latvie&#353;u',
                'nb_NO' => 'Norsk',
                'nl_NL' => 'Nederlands',
                'no_NO' => 'Norsk bokm&#229;l',
                'pl_PL' => 'Polski',
                'pt_BR' => 'Portugu&#234;s Brasileiro',
                'pt_PT' => 'Portugu&#234;s',
                'ro_RO' => 'Rom&#226;n&#259;',
                'ru_RU' => '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
                'sk_SK' => 'Sloven&#269;ina',
                'sl_SI' => 'Sloven&#353;&#269;ina',
                'sr_CS' => 'Srpski',
                'sv_SE' => 'Svenska',
                'tr_TR' => 'T&#252;rk&#231;e',
                'uk_UA' => 'Українська',
                'vi_VN' => 'Ti&#7871;ng Vi&#7879;t',
                'zh_CN' => '&#31616;&#20307;&#20013;&#25991;',
                'zh_TW' => '&#32321;&#39636;&#20013;&#25991;',
                'zh-Hant' => '&#32321;&#39636;&#20013;&#25991; (' . $file . ')',
                'zh_SG' => 'Chinese (Singapore)',
                'ar_SA' => '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;',
                'he_IL' => '&#1506;&#1489;&#1512;&#1497;&#1514;',
                'fa_IR' => '&#1601;&#1575;&#1585;&#1587;&#1610;',
                default => sprintf(
                    /* HINT: File */
                    T_('Unknown %s'),
                    '(' . $file . ')'
                ),
            }; // end switch

            $results[$file] = $name;
        }
    } // end while

    // Sort the list of languages by country code
    ksort($results);

    // Prepend English (US)
    return ["en_US" => "English (US)"] + $results;
}

/**
 * is_rtl
 * This checks whether to be a Right-To-Left language.
 * @param string $locale
 */
function is_rtl($locale): bool
{
    return in_array($locale, ["he_IL", "fa_IR", "ar_SA"]);
}

// Declare apache_request_headers and getallheaders if it don't exists (PHP <= 5.3 + FastCGI)
if (!function_exists('apache_request_headers')) {
    /**
     * @return array
     */
    function apache_request_headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } elseif ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            } elseif ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            }
        }

        return $headers;
    }
}
if (!function_exists('getallheaders')) {
    /**
     * @return array
     */
    function getallheaders(): array
    {
        return apache_request_headers();
    }
}

/**
 * get_current_path
 */
function get_current_path(): string
{
    if (strlen((string) $_SERVER['PHP_SELF'])) {
        $root = $_SERVER['PHP_SELF'];
    } else {
        $root = $_SERVER['REQUEST_URI'];
    }

    return (string) $root;
}

/**
 * get_web_path
 */
function get_web_path(): string
{
    $root = get_current_path();

    return (string) preg_replace('#(.*)/(\w+\.php)$#', '$1', $root);
}

/**
 * get_datetime
 * @param DateTimeInterface|int $time
 * @param string $date_format
 * @param string $time_format
 * @param string $overwrite
 */
function get_datetime($time, $date_format = 'short', $time_format = 'short', $overwrite = ''): string
{
    if ($time instanceof DateTimeInterface) {
        $time = $time->getTimestamp();
    }

    // allow time or date only
    $date_type = ($date_format == 'none') ? IntlDateFormatter::NONE : IntlDateFormatter::SHORT;
    $time_type = ($time_format == 'none') ? IntlDateFormatter::NONE : IntlDateFormatter::SHORT;
    // if no override is set but you have a custom_datetime
    $pattern  = ($overwrite == '') ? (string) AmpConfig::get('custom_datetime', '') : $overwrite;
    $timezone = (!empty(AmpConfig::get('custom_timezone')))
        ? AmpConfig::get('custom_timezone')
        : AmpConfig::get('date_timezone');

    // get your locale and set the date based on that, unless you have 'custom_datetime set'
    $locale = AmpConfig::get('lang', 'en_US');
    $format = new IntlDateFormatter($locale, $date_type, $time_type, $timezone, null, $pattern);

    return $format->format($time) ?: '';
}

/**
 * check_config_values
 * checks to make sure that they have at least set the needed variables
 * @param array $conf
 */
function check_config_values($conf): bool
{
    if (!is_array($conf)) {
        return false;
    }
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
}

/**
 * @param string $val
 * @return int|string
 */
function return_bytes($val)
{
    $val  = trim((string) $val);
    $last = strtolower((string) $val[strlen((string) $val) - 1]);
    switch ($last) {
        case 'g':
            $val = (int)$val * 1024;
            // Intentional break fall-through
        case 'm':
            $val = (int)$val * 1024;
            // Intentional break fall-through
        case 'k':
            $val = (int)$val * 1024;
            break;
    }

    return $val;
}

/**
 * check_config_writable
 * This checks whether we can write the config file
 */
function check_config_writable(): bool
{
    // file eixsts && is writable, or dir is writable
    return ((file_exists(__DIR__ . '/../../config/ampache.cfg.php') && is_writeable(__DIR__ . '/../../config/ampache.cfg.php')) ||
        (!file_exists(__DIR__ . '/../../config/ampache.cfg.php') && is_writeable(__DIR__ . '/../../config/')));
}

/**
 * check_htaccess_rest_writable
 */
function check_htaccess_rest_writable(): bool
{
    return ((file_exists(__DIR__ . '/../../public/rest/.htaccess') && is_writeable(__DIR__ . '/../../public/rest/.htaccess')) ||
        (!file_exists(__DIR__ . '/../../public/rest/.htaccess') && is_writeable(__DIR__ . '/../../public/rest/')));
}

/**
 * check_htaccess_play_writable
 */
function check_htaccess_play_writable(): bool
{
    return ((file_exists(__DIR__ . '/../../public/play/.htaccess') && is_writeable(__DIR__ . '/../../public/play/.htaccess')) ||
        (!file_exists(__DIR__ . '/../../public/play/.htaccess') && is_writeable(__DIR__ . '/../../public/play/')));
}

/**
 * debug_result
 * Convenience function to format the output.
 * @param string|bool $status
 * @param string $value
 * @param string $comment
 */
function debug_result($status = false, $value = null, $comment = ''): string
{
    $class = $status ? 'success' : 'danger';

    if ($value === null) {
        $value = $status ? T_('OK') : T_('Error');
    }

    return '<button type="button" class="btn btn-' . $class . '">' . scrub_out($value) .
        '<em>' . $comment . '</em></button>';
}

/**
 * ampache_error_handler
 *
 * An error handler for ampache that traps as many errors as it can and logs
 * them.
 */
function ampache_error_handler(int $errno, string $errstr, string $errfile, int $errline): bool
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
    $ignores = [
        // We know var is deprecated, shut up
        'var: Deprecated. Please use the public/private/protected modifiers',
        // getid3 spews errors, yay!
        'getimagesize() [',
        'Non-static method getid3',
        'Assigning the return value of new by reference is deprecated',
        // The XML-RPC lib is broken (kinda)
        'used as offset, casting to integer',
    ];

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
    debug_event('log.lib', $log_line, $level, 'ampache');

    return true;
}

/**
 * debug_event
 * This function is called inside Ampache, it's actually a wrapper for the
 * log_event. It checks config for debug and debug_level and only
 * calls log event if both requirements are met.
 * @param string $type
 * @param string $message
 * @param int $level
 * @param string $username
 *
 * @deprecated Use LegacyLogger
 */
function debug_event($type, $message, $level, $username = ''): bool
{
    if (!$username && Core::get_global('user') instanceof User) {
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
}

/**
 * @param string $action
 * @param array|null $catalogs
 * @param array $options
 */
function catalog_worker($action, $catalogs = null, $options = null): void
{
    if (AmpConfig::get('ajax_load')) {
        $sse_url = AmpConfig::get_web_path('/client') . "/server/sse.server.php?worker=catalog&action=" . $action . "&catalogs=" . urlencode(json_encode($catalogs) ?: '');
        if ($options) {
            $sse_url .= "&options=" . urlencode(json_encode($_POST) ?: '');
        }

        echo '<script>';
        echo "sse_worker('$sse_url');";
        echo "</script>\n";
    } else {
        Catalog::process_action($action, $catalogs, $options);
    }
}

/**
 * return_referer
 * returns the script part of the referer address passed by the web browser
 * this is not %100 accurate. Also because this is not passed by us we need
 * to clean it up, take the filename then check for a /admin/ and dump the rest
 */
function return_referer(): string
{
    $referer = Core::get_server('HTTP_REFERER');
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
}

/**
 * show_album_select
 * This displays a select of every album that we've got in Ampache (which can be hella long).
 * It's used by the Edit page and takes a $name and an $album_id
 * @param string $name
 * @param int $album_id
 * @param bool $allow_add
 * @param int $song_id
 * @param bool $allow_none
 * @param int $user_id
 */
function show_album_select($name, $album_id = 0, $allow_add = false, $song_id = 0, $allow_none = false, $user_id = null): void
{
    static $album_id_cnt = 0;

    // Generate key to use for HTML element ID
    if ($song_id) {
        $key = "album_select_" . $song_id;
    } else {
        $key = "album_select_c" . ++$album_id_cnt;
    }

    $sql    = "SELECT `album`.`id`, `album`.`name`, `album`.`prefix` FROM `album`";
    $params = [];
    if ($user_id !== null) {
        $sql .= "INNER JOIN `artist` ON `artist`.`id` = `album`.`album_artist` WHERE `album`.`album_artist` IS NOT NULL AND `artist`.`user` = ? ";
        $params[] = $user_id;
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
        echo "<script>check_inline_song_edit('" . $name . "', " . $song_id . ")</script>\n";
    }
}

/**
 * show_artist_select
 * This is the same as show_album_select except it's *gasp* for artists! How
 * inventive!
 * @param string $name
 * @param int $artist_id
 * @param bool $allow_add
 * @param int $song_id
 * @param bool $allow_none
 * @param int $user_id
 */
function show_artist_select($name, $artist_id = 0, $allow_add = false, $song_id = 0, $allow_none = false, $user_id = null): void
{
    static $artist_id_cnt = 0;
    // Generate key to use for HTML element ID
    if ($song_id) {
        $key = $name . "_select_" . $song_id;
    } else {
        $key = $name . "_select_c" . ++$artist_id_cnt;
    }

    $sql    = "SELECT `id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `name` FROM `artist` ";
    $params = [];
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
        $selected = ($row['id'] == $artist_id)
            ? "selected=\"selected\""
            : '';

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['name']) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_('Add New') . "...</option>\n";
    }

    echo "</select>\n";

    if ($count === 0) {
        echo "<script>check_inline_song_edit('" . $name . "', " . $song_id . ")</script>\n";
    }
}

/**
 * show_catalog_select
 * Yet another one of these buggers. this shows a drop down of all of your
 * catalogs.
 * @param string $name
 * @param int $catalog_id
 * @param string $style
 * @param bool $allow_none
 * @param string $gather_types
 * @param string $catalog_type
 */
function show_catalog_select($name, $catalog_id, $style = '', $allow_none = false, $gather_types = '', $catalog_type = ''): void
{
    echo "<select name=\"$name\" style=\"$style\">\n";

    $params = [];
    $sql    = "SELECT `id`, `name` FROM `catalog` ";
    $where  = "WHERE";
    if (!empty($gather_types)) {
        $sql .= $where . " `gather_types` = ? ";
        $params[] = $gather_types;
        $where    = "AND";
    }

    if (!empty($catalog_type)) {
        $sql .= $where . " `catalog_type` = ? ";
        $params[] = $catalog_type;
    }
    $sql .= "ORDER BY `name`;";
    $db_results = Dba::read($sql, $params);
    $results    = [];
    while ($row = Dba::fetch_assoc($db_results)) {
        $results[] = $row;
    }

    if ($allow_none) {
        echo "\t<option value=\"-1\">" . T_('None') . "</option>\n";
    }

    if (
        empty($results) &&
        !empty($gather_types)
    ) {
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        echo "\t<option value=\"\" selected=\"selected\">" . sprintf(T_('Not Found: %s'), $gather_types) . "</option>\n";
    }

    foreach ($results as $row) {
        $selected = '';
        if ($row['id'] == (string) $catalog_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['name']) . "</option>\n";
    } // end while

    echo "</select>\n";
}

/**
 * show_album_select
 * This displays a select of every album that we've got in Ampache (which can be hella long).
 * It's used by the Edit page and takes a $name and an $album_id
 * @param string $name
 * @param int|null $license_id
 * @param int|null $song_id
 */
function show_license_select($name, $license_id = 0, $song_id = 0): void
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
        echo ">" . scrub_out($row['name']) . "</option>\n";
    } // end while

    echo "</select>\n";
    echo "<a href=\"javascript:show_selected_license_link('" . $key . "');\">" . T_('View License') . " " . Ui::get_material_symbol('exit_to_app') . "</a>";
}

/**
 * show_user_select
 * This one is for users! shows a select/option statement so you can pick a user
 * to blame
 * @param string $name
 * @param string $selected
 * @param string $style
 */
function show_user_select($name, $selected = '', $style = ''): void
{
    echo "<select name=\"$name\" style=\"$style\">\n";
    echo "\t<option value=\"-1\">" . T_('All') . "</option>\n";

    $sql        = "SELECT `id`, `username`, `fullname` FROM `user` ORDER BY `fullname`";
    $db_results = Dba::read($sql);

    while ($row = Dba::fetch_assoc($db_results)) {
        $select_txt = '';
        if ($row['id'] == $selected) {
            $select_txt = 'selected="selected"';
        }
        // If they don't have a full name, revert to the username
        $row['fullname'] = $row['fullname'] ?: $row['username'];

        echo "\t<option value=\"" . $row['id'] . "\" $select_txt>" . scrub_out($row['fullname']) . "</option>\n";
    } // end while users

    echo "</select>\n";
}

function xoutput_headers(): void
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
 * @param bool $callback
 * @param string $type
 * @return mixed|string
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
        return json_encode($array) ?: '';
    }
}

/**
 * display_notification
 * Show a javascript notification to the user
 * @param string $message
 * @param int $timeout
 */
function display_notification($message, $timeout = 5000): void
{
    echo "<script>";
    echo "displayNotification('" . addslashes(json_encode($message, JSON_UNESCAPED_UNICODE) ?: '') . "', " . $timeout . ");";
    echo "</script>\n";
}

/**
 * show_now_playing
 * This shows the Now Playing templates and does some garbage collection
 * this should really be somewhere else
 */
function show_now_playing(): void
{
    Session::garbage_collection();
    Stream::garbage_collection();

    $web_path = AmpConfig::get_web_path('/client');
    $results  = Stream::get_now_playing();
    require_once Ui::find_template('show_now_playing.inc.php');
}

/**
 * @param bool $render
 * @param bool $force
 */
function show_table_render($render = false, $force = false): void
{
    // Include table render javascript only once
    if ($force || !defined('TABLE_RENDERED')) {
        if (!defined('TABLE_RENDERED')) {
            define('TABLE_RENDERED', 1);
        } ?>
        <?php if ($render) { ?>
            <script>
                $(document).ready(function () {
                    sortPlaylistRender();
                });
            </script>
            <?php
        }
    }
}

/**
 * load_gettext
 * Sets up our local gettext settings.
 */
function load_gettext(): bool
{
    $lang   = AmpConfig::get('lang', 'en_US');
    $mopath = __DIR__ . '/../../locale/' . $lang . '/LC_MESSAGES/messages.mo';

    if (file_exists($mopath)) {
        $loader       = new MoLoader();
        $translations = $loader->loadFile($mopath);
        $gettext      = Translator::createFromTranslations($translations);

        TranslatorFunctions::register($gettext);
    }

    return true;
}

/**
 * T_
 * Translate string
 */
function T_(string $msgid): string
{
    if (function_exists('__')) {
        return __($msgid);
    }

    return $msgid;
}

/**
 * @param string $original
 * @param string $plural
 * @param int|string|float $value
 * @return string
 */
function nT_($original, $plural, $value): string
{
    if (function_exists('n__')) {
        return n__($original, $plural, (string)$value);
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
function get_themes(): array
{
    $results = [];

    $lst_files = glob(__DIR__ . '/../../public/client/themes/*/theme.cfg.php');
    if (!$lst_files) {
        debug_event('themes', 'Failed to open /themes directory', 2);

        return $results;
    }

    foreach ($lst_files as $cfg_file) {
        $name = basename(dirname($cfg_file)); // Get last dirname (name of the theme)
        debug_event('themes', "Checking $name", 5);
        $theme_cfg = get_theme($name);
        if (is_null($theme_cfg)) {
            debug_event('themes', "Warning: $name theme config is empty", 1);
            continue;
        }

        $results[$name] = $theme_cfg;
    }

    // Sort by the theme name
    ksort($results);

    return $results;
}

/**
 * get_theme
 * get a single theme and read the config file then return the results
 * @param string $name
 * @return array|bool|false|mixed|null
 */
function get_theme($name)
{
    static $_mapcache = [];

    if (strlen((string) $name) < 1) {
        return false;
    }

    $name = strtolower((string) $name);

    if (array_key_exists($name, $_mapcache)) {
        return $_mapcache[$name];
    }

    $config_file = __DIR__ . "/../../public/client/themes/" . $name . "/theme.cfg.php";
    if (file_exists($config_file)) {
        $results = parse_ini_file($config_file);
        if (is_array($results)) {
            $results['path'] = $name;
            $results['base'] = explode(',', (string)$results['base']);
            $nbbases         = count($results['base']);
            for ($count = 0; $count < $nbbases; $count++) {
                $results['base'][$count] = explode('|', $results['base'][$count]);
            }
            $results['colors'] = explode(',', (string)$results['colors']);
        } else {
            $results = null;
        }
    } else {
        $results = null;
    }

    $_mapcache[$name] = $results;

    return $results;
}

/**
 * Used in graph class also format string
 *
 * @see \Ampache\Module\Util\Graph
 *
 * @param $value
 */
function pGraph_Yformat_bytes($value): string
{
    return Ui::format_bytes($value);
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
        AccessTypeEnum::INTERFACE,
        AccessLevelEnum::CONTENT_MANAGER
    );
}
