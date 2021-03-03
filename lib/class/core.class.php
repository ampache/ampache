<?php
declare(strict_types=0);
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
 * Core Class
 *
 * This is really just a namespace class, it's full of static functions
 * would be replaced by a namespace library once that exists in php
 *
 */
class Core
{
    /**
     * constructor
     * This doesn't do anything
     */
    private function __construct()
    {
        return false;
    } // construction

    /**
     * autoload
     *
     * This function automatically loads any missing classes as they are
     * needed so that we don't use a million include statements which load
     * more than we need.
     * @param $class
     */
    public static function autoload($class)
    {
        $possiblePaths = array();
        if (strpos($class, '\\') === false) {
            $possiblePaths = self::getNonNamespacedPaths($class);
        } else {
            $possiblePaths = self::getNamespacedPaths($class);
        }

        foreach ($possiblePaths as $path) {
            if (is_file($path) && self::is_readable($path)) {
                require_once($path);
                self::executeAutoCall($class);
            } else {
                debug_event(self::class, "'$class' not found!", 1);
            }
        }
    }

    /**
     * executeAutoCall
     * Execute _auto_init if available
     * @param string $class
     */
    private static function executeAutoCall($class)
    {
        $autocall = array($class, '_auto_init');
        if (is_callable($autocall)) {
            call_user_func($autocall);
        }
    }

    /**
     * get_global
     * Return a $GLOBAL variable instead of calling directly
     *
     * @param string $variable
     * @return mixed
     */
    public static function get_global($variable)
    {
        return $GLOBALS[$variable];
    }

    /**
     * get_request
     * Return a $REQUEST variable instead of calling directly
     *
     * @param string $variable
     * @return string
     */
    public static function get_request($variable)
    {
        if (filter_input(INPUT_POST, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== null) {
            return filter_input(INPUT_POST, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if (filter_input(INPUT_GET, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== null) {
            return filter_input(INPUT_GET, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if (isset($_REQUEST[$variable])) {
            return filter_var($_REQUEST[$variable], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if ($_REQUEST[$variable] === null) {
            return '';
        }

        return $_REQUEST[$variable];
    }

    /**
     * get_get
     * Return a $GET variable instead of calling directly
     *
     * @param string $variable
     * @return string
     */
    public static function get_get($variable)
    {
        if (filter_has_var(INPUT_GET, $variable)) {
            return filter_input(INPUT_GET, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if (isset($_GET[$variable])) {
            return filter_var($_GET[$variable], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if ($_GET[$variable] === null) {
            return '';
        }

        return $_GET[$variable];
    }

    /**
     * get_cookie
     * Return a $COOKIE variable instead of calling directly
     *
     * @param string $variable
     * @return string
     */
    public static function get_cookie($variable)
    {
        if (filter_has_var(INPUT_COOKIE, $variable)) {
            return filter_input(INPUT_COOKIE, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if (isset($_COOKIE[$variable])) {
            return filter_var($_COOKIE[$variable], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if ($_COOKIE[$variable] === null) {
            return '';
        }

        return $_COOKIE[$variable];
    }

    /**
     * get_server
     * Return a $SERVER variable instead of calling directly
     *
     * @param string $variable
     * @return string
     */
    public static function get_server($variable)
    {
        if (filter_has_var(INPUT_SERVER, $variable)) {
            return filter_input(INPUT_SERVER, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        // INPUT_SERVER can sometimes fail
        if (filter_has_var(INPUT_ENV, $variable)) {
            return filter_input(INPUT_ENV, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if (isset($_SERVER[$variable])) {
            return filter_var($_SERVER[$variable], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if ($_SERVER[$variable] === null) {
            return '';
        }

        return $_SERVER[$variable];
    }

    /**
     * get_post
     * Return a $POST variable instead of calling directly
     *
     * @param string $variable
     * @return string
     */
    public static function get_post($variable)
    {
        if (filter_has_var(INPUT_POST, $variable)) {
            return filter_input(INPUT_POST, $variable, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if (isset($_POST[$variable])) {
            return filter_var($_POST[$variable], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        if ($_POST[$variable] === null) {
            return '';
        }

        return $_POST[$variable];
    }

    /**
     * get_user_ip
     * check for the ip of the request
     *
     * @return string
     */
    public static function get_user_ip()
    {
        // get the x forward if it's valid
        if (filter_var(Core::get_server('HTTP_X_FORWARDED_FOR'), FILTER_VALIDATE_IP)) {
            return filter_var(Core::get_server('HTTP_X_FORWARDED_FOR'), FILTER_VALIDATE_IP);
        }

        return filter_var(Core::get_server('REMOTE_ADDR'), FILTER_VALIDATE_IP);
    }
    /**
     * Place a new key on a specific position in array
     * @param array $array
     * @param integer $position
     * @param array $add
     * @return array
     */
    private static function insertInArray(array $array, $position, array $add)
    {
        return array_slice($array, 0, $position, true) +
                $add +
                array_slice($array, $position, null, true);
    }

    /**
     * Get possible filepaths of namespaced classes
     * @param string $class
     * @return string[]
     */
    private static function getNamespacedPaths($class)
    {
        $possiblePaths   = array();
        $namespaceParts  = explode('\\', (string) $class);
        $possiblePaths[] = AmpConfig::get('prefix') . '/modules/' . implode('/', $namespaceParts) . '.php';

        $classedPath = array('path' => AmpConfig::get('prefix')) +
                self::insertInArray($namespaceParts, 1, array('add' => 'class'));
        $possiblePaths[] = implode('/', $classedPath) . '.php';

        return $possiblePaths;
    }

    /**
     * Get possible filepaths of non namespaced classes
     * @param string $class
     * @return string[]
     */
    private static function getNonNamespacedPaths($class)
    {
        $possiblePaths   = array();
        $possiblePaths[] = AmpConfig::get('prefix') . '/lib/class/' .
                strtolower((string) $class) . '.class.php';

        return $possiblePaths;
    }

    /**
     * form_register
     * This registers a form with a SID, inserts it into the session
     * variables and then returns a string for use in the HTML form
     * @param string $name
     * @param string $type
     * @return string
     */
    public static function form_register($name, $type = 'post')
    {
        // Make ourselves a nice little sid
        $sid    = md5(uniqid((string) rand(), true));
        $window = AmpConfig::get('session_length');
        $expire = time() + $window;

        // Register it
        $_SESSION['forms'][$sid] = array('name' => $name, 'expire' => $expire);
        if (!isset($_SESSION['forms'][$sid])) {
            debug_event(self::class, "Form $sid not found in session, failed to register!", 2);
        } else {
            debug_event(self::class, "Registered $type form $name with SID $sid and expiration $expire ($window seconds from now)", 5);
        }

        switch ($type) {
            case 'get':
                $string = $sid;
                break;
            case 'post':
            default:
                $string = '<input type="hidden" name="form_validation" value="' . $sid . '" />';
                break;
        } // end switch on type

        return $string;
    } // form_register

    /**
     * form_verify
     *
     * This takes a form name and then compares it with the posted sid, if
     * they don't match then it returns false and doesn't let the person
     * continue
     * @param string $name
     * @param string $type
     * @return boolean
     */
    public static function form_verify($name, $type = 'post')
    {
        switch ($type) {
            case 'post':
                $sid = $_POST['form_validation'];
                break;
            case 'get':
                $sid = $_GET['form_validation'];
                break;
            case 'cookie':
                $sid = $_COOKIE['form_validation'];
                break;
            case 'request':
                $sid = $_REQUEST['form_validation'];
                break;
            default:
                return false;
        }

        if (!isset($_SESSION['forms'][$sid])) {
            debug_event(self::class, "Form $sid not found in session, rejecting request", 2);

            return false;
        }

        $form = $_SESSION['forms'][$sid];
        unset($_SESSION['forms'][$sid]);

        if ($form['name'] == $name) {
            debug_event(self::class, "Verified SID $sid for $type form $name", 5);
            if ($form['expire'] < time()) {
                debug_event(self::class, "Form $sid is expired, rejecting request", 2);

                return false;
            }

            return true;
        }

        // OMG HAX0RZ
        debug_event(self::class, "$type form $sid failed consistency check, rejecting request", 2);

        return false;
    } // form_verify

    /**
     * gen_secure_token
     *
     * This generates a cryptographically secure token.
     * Returns a token of the required bytes length, as a string. Returns false
     * if it could not generate a cryptographically secure token.
     * @param integer $length
     * @return false|string
     * @throws Exception
     */
    public static function gen_secure_token($length)
    {
        $buffer = '';
        if (function_exists('random_bytes')) {
            $buffer = random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $buffer = openssl_random_pseudo_bytes($length);
        } elseif (file_exists('/dev/random') && is_readable('/dev/random')) {
            $buffer = file_get_contents('/dev/random', false, null, -1, $length);
        } else {
            return false;
        }

        return bin2hex($buffer);
    }

    /**
     * image_dimensions
     * This returns the dimensions of the passed song of the passed type
     * returns an empty array if PHP-GD is not currently installed, returns
     * false on error
     *
     * @param string $image_data
     * @return array
     */
    public static function image_dimensions($image_data)
    {
        if (!function_exists('ImageCreateFromString')) {
            return array('width' => 0, 'height' => 0);
        }

        if (empty($image_data)) {
            debug_event(self::class, "Cannot create image from empty data", 2);

            return array('width' => 0, 'height' => 0);
        }

        $image = ImageCreateFromString($image_data);

        if ($image == false) {
            return array('width' => 0, 'height' => 0);
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        if (!$width || !$height) {
            return array('width' => 0, 'height' => 0);
        }

        return array('width' => $width, 'height' => $height);
    } // image_dimensions

    /**
     * is_readable
     *
     * Replacement function because PHP's is_readable is buggy:
     * https://bugs.php.net/bug.php?id=49620
     *
     * @param string $path
     * @return boolean
     */
    public static function is_readable($path)
    {
        if (is_dir($path)) {
            $handle = opendir($path);
            if ($handle === false) {
                return false;
            }
            closedir($handle);

            return true;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        fclose($handle);

        return true;
    }

    /**
     * get_filesize
     * Get a file size. This because filesize() doesn't work on 32-bit OS with files > 2GB
     * @param $filename
     * @return integer
     */
    public static function get_filesize($filename)
    {
        $size = filesize($filename);
        if ($size === false) {
            $filepointer = fopen($filename, 'rb');
            if (!$filepointer) {
                return false;
            }
            $offset = PHP_INT_MAX - 1;
            $size   = (float) $offset;
            if (!fseek($filepointer, $offset)) {
                return false;
            }
            $chunksize = 8192;
            while (!feof($filepointer)) {
                $size += strlen(fread($filepointer, $chunksize));
            }
        } elseif ($size < 0) {
            // Handle overflowed integer...
            $size = sprintf("%u", $size);
        }

        return $size;
    }

    /**
     * conv_lc_file
     *
     * Convert site charset filename to local charset filename for file operations
     * @param string $filename
     * @return string
     */
    public static function conv_lc_file($filename)
    {
        $lc_filename  = $filename;
        $site_charset = AmpConfig::get('site_charset');
        $lc_charset   = AmpConfig::get('lc_charset');
        if ($lc_charset && $lc_charset != $site_charset) {
            if (function_exists('iconv')) {
                $lc_filename = iconv($site_charset, $lc_charset, $filename);
            }
        }

        return $lc_filename;
    }

    /**
     * is_session_started
     *
     * Universal function for checking session status.
     * @return boolean
     */
    public static function is_session_started()
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE;
            } else {
                return session_id() === '' ? false : true;
            }
        }

        return false;
    }

    /**
     * is_class_typeof
     *
     * @param $classname
     * @param string $typeofname
     * @return boolean
     */
    private static function is_class_typeof($classname, $typeofname)
    {
        if (class_exists($classname)) {
            return in_array($typeofname, array_map('strtolower', class_implements($classname)));
        }

        return false;
    }

    /**
     * @param $classname
     * @return boolean
     */
    public static function is_playable_item($classname)
    {
        return self::is_class_typeof($classname, 'playable_item');
    }

    /**
     * @param $classname
     * @return boolean
     */
    public static function is_library_item($classname)
    {
        return self::is_class_typeof($classname, 'library_item');
    }

    /**
     * @param $classname
     * @return boolean
     */
    public static function is_media($classname)
    {
        return self::is_class_typeof($classname, 'media');
    }

    /**
     * @return string
     */
    public static function get_reloadutil()
    {
        $play_type = AmpConfig::get('play_type');

        return ($play_type == "stream" || $play_type == "democratic" || !AmpConfig::get('ajax_load')) ? "reloadUtil" : "reloadDivUtil";
    }

    /**
     * requests_options
     * @param array $options
     * @return array
     */
    public static function requests_options($options = array())
    {
        if (!isset($options['proxy'])) {
            if (AmpConfig::get('proxy_host') && AmpConfig::get('proxy_port')) {
                $proxy   = array();
                $proxy[] = AmpConfig::get('proxy_host') . ':' . AmpConfig::get('proxy_port');
                if (AmpConfig::get('proxy_user')) {
                    $proxy[] = AmpConfig::get('proxy_user');
                    $proxy[] = AmpConfig::get('proxy_pass');
                }

                $options['proxy'] = $proxy;
            }
        }

        return $options;
    }

    /**
     * get_tmp_dir
     *
     * @return string
     */
    public static function get_tmp_dir()
    {
        if (AmpConfig::get('tmp_dir_path')) {
            return AmpConfig::get('tmp_dir_path');
        }
        if (function_exists('sys_get_temp_dir')) {
            $tmp_dir = sys_get_temp_dir();
        } else {
            if (strpos(PHP_OS, 'WIN') === 0) {
                $tmp_dir = $_ENV['TMP'];
                if (!isset($tmp_dir)) {
                    $tmp_dir = 'C:\Windows\Temp';
                }
            } else {
                $tmp_dir = @$_ENV['TMPDIR'];
                if (!isset($tmp_dir)) {
                    $tmp_dir = '/tmp';
                }
            }
        }

        return $tmp_dir;
    }
} // end core.class
