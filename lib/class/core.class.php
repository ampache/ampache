<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
            if (is_file($path) && Core::is_readable($path)) {
                require_once($path);
                self::executeAutoCall($class);
            } else {
                debug_event('autoload', "'$class' not found!", 1);
            }
        }
    }

    /**
     * Execute _auto_init if availlable
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
     * @return string
     */
    private static function getNamespacedPaths($class)
    {
        $possiblePaths   = array();
        $namespaceParts  = explode('\\', $class);
        $possiblePaths[] = AmpConfig::get('prefix') . '/modules/' . implode('/', $namespaceParts) . '.php';

        $classedPath = array('path' => AmpConfig::get('prefix')) +
                self::insertInArray($namespaceParts, 1, array('add' => 'class'));
        $possiblePaths[] = implode('/', $classedPath) . '.php';

        return $possiblePaths;
    }

    /**
     * Get possible filepaths of non namespaced classes
     * @param string $class
     * @return string
     */
    private static function getNonNamespacedPaths($class)
    {
        $possiblePaths   = array();
        $possiblePaths[] = AmpConfig::get('prefix') . '/lib/class/' .
                strtolower($class) . '.class.php';

        return $possiblePaths;
    }

    /**
     * form_register
     * This registers a form with a SID, inserts it into the session
     * variables and then returns a string for use in the HTML form
     */
    public static function form_register($name, $type = 'post')
    {
        // Make ourselves a nice little sid
        $sid    =  md5(uniqid(rand(), true));
        $window = AmpConfig::get('session_length');
        $expire = time() + $window;

        // Register it
        $_SESSION['forms'][$sid] = array('name' => $name, 'expire' => $expire);
        debug_event('Core', "Registered $type form $name with SID $sid and expiration $expire ($window seconds from now)", 5);

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
            debug_event('Core', "Form $sid not found in session, rejecting request", 2);

            return false;
        }

        $form = $_SESSION['forms'][$sid];
        unset($_SESSION['forms'][$sid]);

        if ($form['name'] == $name) {
            debug_event('Core', "Verified SID $sid for $type form $name", 5);
            if ($form['expire'] < time()) {
                debug_event('Core', "Form $sid is expired, rejecting request", 2);

                return false;
            }

            return true;
        }

        // OMG HAX0RZ
        debug_event('Core', "$type form $sid failed consistency check, rejecting request", 2);

        return false;
    } // form_verify

    /**
     * gen_secure_token
     *
     * This generates a cryptographically secure token.
     * Returns a token of the required bytes length, as a string. Returns false
     * if it could not generate a cryptographically secure token.
     */
    public static function gen_secure_token($length)
    {
        $buffer = '';
        if (function_exists('random_bytes')) {
            $buffer = random_bytes($length);
        } elseif (function_exists('mcrypt_create_iv')) {
            $buffer = mcrypt_create_iv($length, MCRYPT_DEV_RANDOM);
        } elseif (phpversion() > "5.6.12" && function_exists('openssl_random_pseudo_bytes')) {
            // PHP version check for https://bugs.php.net/bug.php?id=70014
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
    */
    public static function image_dimensions($image_data)
    {
        if (!function_exists('ImageCreateFromString')) {
            return false;
        }

        if (empty($image_data)) {
            debug_event('Core', "Cannot create image from empty data", 2);

            return false;
        }

        $image = ImageCreateFromString($image_data);

        if (!$image) {
            return false;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        if (!$width || !$height) {
            return false;
        }

        return array('width' => $width,'height' => $height);
    } // image_dimensions

    /*
     * is_readable
     *
     * Replacement function because PHP's is_readable is buggy:
     * https://bugs.php.net/bug.php?id=49620
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
     */
    public static function get_filesize($filename)
    {
        $size = filesize($filename);
        if ($size === false) {
            $fp = fopen($filename, 'rb');
            if (!$fp) {
                return false;
            }
            $offset = PHP_INT_MAX - 1;
            $size   = (float) $offset;
            if (!fseek($fp, $offset)) {
                return false;
            }
            $chunksize = 8192;
            while (!feof($fp)) {
                $size += strlen(fread($fp, $chunksize));
            }
        } elseif ($size < 0) {
            // Handle overflowed integer...
            $size = sprintf("%u", $size);
        }

        return $size;
    }

    /*
     * conv_lc_file
     *
     * Convert site charset filename to local charset filename for file operations
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

    /*
     * is_session_started
     *
     * Universal function for checking session status.
     */
    public static function is_session_started()
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === '' ? false : true;
            }
        }

        return false;
    }

    private static function is_class_typeof($classname, $typeofname)
    {
        if (class_exists($classname)) {
            return in_array($typeofname, array_map('strtolower', class_implements($classname)));
        }

        return false;
    }

    public static function is_playable_item($classname)
    {
        return self::is_class_typeof($classname, 'playable_item');
    }

    public static function is_library_item($classname)
    {
        return self::is_class_typeof($classname, 'library_item');
    }

    public static function is_media($classname)
    {
        return self::is_class_typeof($classname, 'media');
    }

    public static function get_reloadutil()
    {
        return (AmpConfig::get('play_type') == "stream" || !AmpConfig::get('ajax_load')) ? "reloadUtil" : "reloadDivUtil";
    }

    public static function requests_options($options = null)
    {
        if ($options == null) {
            $options = array();
        }

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
    
    public static function get_tmp_dir()
    {
        $tmp_dir = AmpConfig::get('tmp_dir_path');
        if (empty($store_path)) {
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
        }
        
        return $tmp_dir;
    }
} // Core
