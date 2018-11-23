<?php

/*
 * This class is an helper to quickly migrate from Ampache legacy to Lavarel
 * IT SHOULD BE REMOVED SOON to have a better code design
 */

namespace App\Classes;

use Illuminate\Support\Str;

class UI
{
    private static $_classes;
    private static $_ticker;
    
    /**
     * check_ticker
     *
     * Stupid little cutesie thing to ratelimit output of long-running
     * operations.
     */
    public static function check_ticker()
    {
        if (!isset(self::$_ticker) || (time() > self::$_ticker + 1)) {
            self::$_ticker = time();

            return true;
        }

        return false;
    }

    /**
     * flip_class
     *
     * First initialised with an array of two class names. Subsequent calls
     * reverse the array then return the first element.
     */
    public static function flip_class($classes = null)
    {
        if (is_array($classes)) {
            self::$_classes = $classes;
        } else {
            self::$_classes = array_reverse(self::$_classes);
        }

        return self::$_classes[0];
    }

    /**
     * format_bytes
     *
     * Turns a size in bytes into the best human-readable value
     */
    public static function format_bytes($value, $precision = 2)
    {
        $pass = 0;
        while (strlen(floor($value)) > 3) {
            $value /= 1024;
            $pass++;
        }

        switch ($pass) {
            case 1: $unit  = 'kB'; break;
            case 2: $unit  = 'MB'; break;
            case 3: $unit  = 'GB'; break;
            case 4: $unit  = 'TB'; break;
            case 5: $unit  = 'PB'; break;
            default: $unit = 'B'; break;
        }

        return round($value, $precision) . ' ' . $unit;
    }
    
    /**
     * unformat_bytes
     *
     * Parses a human-readable size
     */
    public static function unformat_bytes($value)
    {
        if (preg_match('/^([0-9]+) *([[:alpha:]]+)$/', $value, $matches)) {
            $value = $matches[1];
            $unit  = strtolower(substr($matches[2], 0, 1));
        } else {
            return $value;
        }

        switch ($unit) {
            case 'p':
                $value *= 1024;
                // no break
            case 't':
                $value *= 1024;
                // no break
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
    
    /**
     * update_text
     *
     * Convenience function that, if the output is going to a browser,
     * blarfs JS to do a fancy update.  Otherwise it just outputs the text.
     */
    public static function update_text($field, $value)
    {
        if (defined('CLI')) {
            echo $value . "\n";

            return;
        }

        static $id = 1;

        if (defined('SSE_OUTPUT')) {
            echo "id: " . $id . "\n";
            echo "data: displayNotification('" . json_encode($value) . "', 5000)\n\n";
        } else {
            if (!empty($field)) {
                echo "<script>updateText('" . $field . "', '" . json_encode($value) . "');</script>\n";
            } else {
                echo "<br />" . $value . "<br /><br />\n";
            }
        }

        ob_flush();
        flush();
        $id++;
    }
    
    public static function is_grid_view($type)
    {
        $isgv = true;
        $cn   = 'browse_' . $type . '_grid_view';
        if (isset($_COOKIE[$cn])) {
            $isgv = ($_COOKIE[$cn] == 'true');
        }

        return $isgv;
    }
    
    public static function get_current_sidebar_tab()
    {
        $sidebar_tab = session('sidebar_tab', 'home');

        return $sidebar_tab;
    }
    
    /**
     * check_iconv
     *
     * Checks to see whether iconv is available;
     */
    public static function check_iconv()
    {
        if (function_exists('iconv') && function_exists('iconv_substr')) {
            return true;
        }
    
        return false;
    }
    
    /**
     * clean_utf8
     *
     * Removes characters that aren't valid in XML (which is a subset of valid
     * UTF-8, but close enough for our purposes.)
     * See http://www.w3.org/TR/2006/REC-xml-20060816/#charsets
     */
    public static function clean_utf8($string)
    {
        if ($string) {
            $clean = preg_replace('/[^\x{9}\x{a}\x{d}\x{20}-\x{d7ff}\x{e000}-\x{fffd}\x{10000}-\x{10ffff}]|[\x{7f}-\x{84}\x{86}-\x{9f}\x{fdd0}-\x{fddf}\x{1fffe}-\x{1ffff}\x{2fffe}-\x{2ffff}\x{3fffe}-\x{3ffff}\x{4fffe}-\x{4ffff}\x{5fffe}-\x{5ffff}\x{6fffe}-\x{6ffff}\x{7fffe}-\x{7ffff}\x{8fffe}-\x{8ffff}\x{9fffe}-\x{9ffff}\x{afffe}-\x{affff}\x{bfffe}-\x{bffff}\x{cfffe}-\x{cffff}\x{dfffe}-\x{dffff}\x{efffe}-\x{effff}\x{ffffe}-\x{fffff}\x{10fffe}-\x{10ffff}]/u', '', $string);

            // Other cleanup regex. Takes too long to process.
            /*$regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
            $clean = preg_replace($regex, '$1', $string);*/

            if ($clean) {
                return $clean;
            }
        }
    }
    
    /*
     * Direct access to .env file for programatically updating entries.
     * Sometiimes necessary because env variables only change when new session started.
     */
    
    public static function updateEnv($env_vars)
    {
        $envFile = base_path('.env');
        $envStr  =file_get_contents($envFile);
        $keys    = array_keys($env_vars);
        
        foreach ($keys as $key) {
            switch ($key) {
                case 'DB_DATABASE':
                    $success = preg_match("~(?m)^DB_DATABASE=([\_\-\w]+)$~", $envStr, $olddb);
                    $envStr  =str_replace("DB_DATABASE=" . $olddb[1], "DB_DATABASE=" . $env_vars['DB_DATABASE'], $envStr);
                    break;
                case 'DB_HOST':
                    $success = preg_match("~(?m)^DB_HOST=(\w+)$~", $envStr, $oldhost);
                    $envStr  =str_replace("DB_HOST=" . $oldhost[1], "DB_HOST=" . $env_vars['DB_HOST'], $envStr);
                    break;
                case 'DB_PORT':
                    $success = preg_match("~(?m)^DB_PORT=(\w+)$~", $envStr, $oldport);
                    $envStr  =str_replace("DB_PORT=" . $oldport[1], "DB_PORT=" . $env_vars['DB_PORT'], $envStr);
                    break;
                case 'DB_USERNAME':
                    $success = preg_match("~(?m)^DB_USERNAME=(\w+)$~", $envStr, $olduser);
                    $envStr  =str_replace("DB_USERNAME=" . $olduser[1], "DB_USERNAME=" . $env_vars['DB_USERNAME'], $envStr);
                    break;
                case 'DB_PASSWORD':
                    $success = preg_match("~(?m)^DB_PASSWORD=(\w+)$~", $envStr, $oldpassword);
                    $envStr  =str_replace("DB_PASSWORD=" . $oldpassword[1], "DB_PASSWORD=" . $env_vars['DB_PASSWORD'], $envStr);
                    break;
                case 'APP_INSTALLED':
                    $success = preg_match("~(?m)^APP_INSTALLED=(\w+)$~", $envStr, $oldpassword);
                    $envStr  =str_replace("APP_INSTALLED=" . $oldpassword[1], "APP_INSTALLED=" . $env_vars['APP_INSTALLED'], $envStr);
                    break;
                default:
            }
        }
        file_put_contents(base_path('.env'), $envStr);
    }
    
    
    public static function getEnv($env_vars, $default = null)
    {
        $envFile = base_path('.env');
        $envStr  =file_get_contents($envFile);
        
        switch ($env_vars) {
                case 'DB_DATABASE':
                    $success = preg_match("~(?m)^DB_DATABASE=([\_\-\w]+)$~", $envStr, $result);
                    break;
                case 'DB_HOST':
                    $success = preg_match("~(?m)^DB_HOST=(\w+)$~", $envStr, $result);
                     break;
                case 'DB_PORT':
                    $success = preg_match("~(?m)^DB_PORT=(\w+)$~", $envStr, $result);
                     break;
                case 'DB_USERNAME':
                    $success = preg_match("~(?m)^DB_USERNAME=(\w+)$~", $envStr, $result);
                    break;
                case 'DB_PASSWORD':
                    $success = preg_match("~(?m)^DB_PASSWORD=(\w+)$~", $envStr, $result);
                    break;
                case 'APP_INSTALLED':
                    $success = preg_match("~(?m)^APP_INSTALLED=(\w+)$~", $envStr, $result);
                    break;
                default:
            }
        if ($result[1] === false) {
            return value($default);
        }
        if ($result[1] === true) {
            return value($default);
        }
            
        switch (strtolower($result[1])) {
                case 'true':
                case '(true)':
                    return true;
                case 'false':
                case '(false)':
                    return false;
                case 'empty':
                case '(empty)':
                    return '';
                case 'null':
                case '(null)':
                    return;
            }
            
        if (strlen($result[1]) > 1 && Str::startsWith($result[1], '"') && Str::endsWith($result[1], '"')) {
            return substr($result[1], 1, -1);
        }
            
        return $result[1];
    }
}
