<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
        if (strpos($class, '\\') === false) {
            $file = AmpConfig::get('prefix') . '/lib/class/' .
                strtolower($class) . '.class.php';

            if (Core::is_readable($file)) {
                require_once $file;

                // Call _auto_init if it exists
                $autocall = array($class, '_auto_init');
                if (is_callable($autocall)) {
                    call_user_func($autocall);
                }
            } else {
                debug_event('autoload', "'$class' not found!", 1);
            }
        } else {
            // Class with namespace are not used by Ampache but probably by modules
            $split = explode('\\', $class);
            $path = AmpConfig::get('prefix') . '/modules';
            for ($i = 0; $i < count($split); ++$i) {
                $path .= '/' . $split[$i];
                if ($i != count($split)-1) {
                    if (!is_dir($path)) {
                        break;
                    }
                } else {
                    $path .= '.php';
                    if (Core::is_readable($path)) {
                        require_once $path;
                    }
                }
            }
        }
    }

    /**
     * form_register
     * This registers a form with a SID, inserts it into the session
     * variables and then returns a string for use in the HTML form
     */
    public static function form_register($name, $type = 'post')
    {
        // Make ourselves a nice little sid
        $sid =  md5(uniqid(rand(), true));
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
     * image_dimensions
    * This returns the dimensions of the passed song of the passed type
    * returns an empty array if PHP-GD is not currently installed, returns
    * false on error
    */
    public static function image_dimensions($image_data)
    {
        if (!function_exists('ImageCreateFromString')) { return false; }

        $image = ImageCreateFromString($image_data);

        if (!$image) { return false; }

        $width = imagesx($image);
        $height = imagesy($image);

        if (!$width || !$height) { return false; }

        return array('width'=>$width,'height'=>$height);

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
            $size = (float) $offset;
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
        $lc_filename = $filename;
        $site_charset = AmpConfig::get('site_charset');
        $lc_charset = AmpConfig::get('lc_charset');
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
        if (php_sapi_name() !== 'cli' ) {
            if (version_compare(phpversion(), '5.4.0', '>=') ) {
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
        return (AmpConfig::get('play_type') == "stream") ? "reloadUtil" : "reloadDivUtil";
    }
} // Core
