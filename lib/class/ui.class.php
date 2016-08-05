<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

// A collection of methods related to the user interface

class UI
{
    private static $_classes;
    private static $_ticker;
    private static $_icon_cache;

    public function __construct()
    {
        return false;
    }

    /**
     * find_template
     *
     * Return the path to the template file wanted. The file can be overwriten
     * by the theme if it's not a php file, or if it is and if option
     * allow_php_themes is set to true.
     */
    public static function find_template($template)
    {
        $path      = AmpConfig::get('theme_path') . '/templates/' . $template;
        $realpath  = AmpConfig::get('prefix') . $path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (($extension != 'php' || AmpConfig::get('allow_php_themes'))
           && file_exists($realpath) && is_file($realpath)) {
            return $path;
        } else {
            return '/templates/' . $template;
        }
    }

    /**
     * access_denied
     *
     * Throw an error when they try to do something naughty.
     */
    public static function access_denied($error = 'Access Denied')
    {
        // Clear any buffered crap
        ob_end_clean();
        header("HTTP/1.1 403 $error");
        require_once AmpConfig::get('prefix') . UI::find_template('show_denied.inc.php');
        exit;
    }

    /**
     * ajax_include
     *
     * Does some trickery with the output buffer to return the output of a
     * template.
     */
    public static function ajax_include($template)
    {
        ob_start();
        require AmpConfig::get('prefix') . UI::find_template('') . $template;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
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

            debug_event('UI', 'Charset cleanup failed, something might break', 1);
        }
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
            case 't':
                $value *= 1024;
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * get_icon
     *
     * Returns an <img> tag for the specified icon
     */
    public static function get_icon($name, $title = null, $id = null)
    {
        $bUseSprite = file_exists(AmpConfig::get('prefix') . AmpConfig::get('theme_path') . '/images/icons.sprite.png');

        if (is_array($name)) {
            $hover_name = $name[1];
            $name       = $name[0];
        }

        $title = $title ?: T_(ucfirst($name));

        $icon_url = self::_find_icon($name);
        if (isset($hover_name)) {
            $hover_url = self::_find_icon($hover_name);
        }
        if ($bUseSprite) {
            $tag = '<span class="sprite sprite-icon_' . $name . '" ';
        } else {
            $tag = '<img src="' . $icon_url . '" ';
        }

        if ($id) {
            $tag .= 'id="' . $id . '" ';
        }

        $tag .= 'alt="' . $title . '" ';
        $tag .= 'title="' . $title . '" ';

        if (isset($hover_name) && isset($hover_url)) {
            $tag .= 'onmouseover="this.src=\'' . $hover_url . '\'; return true;"';
            $tag .= 'onmouseout="this.src=\'' . $icon_url . '\'; return true;" ';
        }

        if ($bUseSprite) {
            $tag .= '></span>';
        } else {
            $tag .= '/>';
        }
        return $tag;
    }

    /**
     * _find_icon
     *
     * Does the finding icon thing
     */
    private static function _find_icon($name)
    {
        if (isset(self::$_icon_cache[$name]) && $url = self::$_icon_cache[$name]) {
            return $url;
        }

        $filename = 'icon_' . $name . '.png';
        $path     = AmpConfig::get('theme_path') . '/images/icons/';
        if (!file_exists(AmpConfig::get('prefix') . $path . $filename)) {
            $path = '/images/';
        }
        $url                      = AmpConfig::get('web_path') . $path . $filename;
        self::$_icon_cache[$name] = $url;

        return $url;
    }

    /**
     * show_header
     *
     * For now this just shows the header template
     */
    public static function show_header()
    {
        require_once AmpConfig::get('prefix') . UI::find_template('header.inc.php');
    }

    /**
     * show_footer
     *
     * Shows the footer template and possibly profiling info.
     */
    public static function show_footer()
    {
        if (!defined("TABLE_RENDERED")) {
            show_table_render();
        }

        $plugins = Plugin::get_plugins('display_on_footer');
        foreach ($plugins as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load($GLOBALS['user'])) {
                $plugin->_plugin->display_on_footer();
            }
        }

        require_once AmpConfig::get('prefix') . UI::find_template('footer.inc.php');
        if (isset($_REQUEST['profiling'])) {
            Dba::show_profile();
        }
    }

    /**
     * show_box_top
     *
     * This shows the top of the box.
     */
    public static function show_box_top($title = '', $class = '')
    {
        require AmpConfig::get('prefix') . UI::find_template('show_box_top.inc.php');
    }

    /**
     * show_box_bottom
     *
     * This shows the bottom of the box
     */
    public static function show_box_bottom()
    {
        require AmpConfig::get('prefix') . UI::find_template('show_box_bottom.inc.php');
    }

    public static function show_custom_style()
    {
        if (AmpConfig::get('custom_login_logo')) {
            echo "<style>#loginPage #headerlogo, #registerPage #headerlogo { background-image: url('" . AmpConfig::get('custom_login_logo') . "') !important; }</style>";
        }

        $favicon = AmpConfig::get('custom_favicon') ?: AmpConfig::get('web_path') . "/favicon.ico";
        echo "<link rel='shortcut icon' href='" .  $favicon . "' />\n";
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

    public static function get_logo_url()
    {
        if (AmpConfig::get('custom_logo')) {
            return AmpConfig::get('custom_logo');
        } else {
            return AmpConfig::get('web_path') . AmpConfig::get('theme_path') . '/images/ampache.png';
        }
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
}
