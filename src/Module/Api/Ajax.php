<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;

/**
 * Ajax class
 *
 * This class is specifically for setting up/printing out ajax related
 * elements onto a page. It takes care of the observing and all that
 * raz-a-ma-taz.
 *
 */
class Ajax
{
    private static $include_override;
    private static $counter = 0;

    /**
     * constructor
     * This is what is called when the class is loaded
     */
    public function __construct()
    {
        // Rien a faire
    } // constructor

    /**
     * observe
     * This returns a string with the correct and full ajax 'observe' stuff
     * from jQuery
     * @param string $source
     * @param string $method
     * @param string $action
     * @param string $confirm
     */
    public static function observe($source, $method, $action, $confirm = ''): string
    {
        $non_quoted = array('document', 'window');

        if (in_array($source, $non_quoted)) {
            $source_txt = $source;
        } else {
            $source_txt = "'#$source'";
        }

        $observe   = "<script>";
        $methodact = ($method == 'click') ? "update_action();" : "";
        if (AmpConfig::get('ajax_load') && $method == 'load') {
            $source_txt = "$( document ).ready(";
        } else {
            $source_txt = "$(" . $source_txt . ").on('" . $method . "', ";
        }
        if (!empty($confirm)) {
            $observe .= $source_txt . "function(){ " . $methodact . " if (confirm(\"" . $confirm . "\")) { " . $action . " }});";
        } else {
            $observe .= $source_txt . "function(){ " . $methodact . " " . $action . ";});";
        }
        $observe .= "</script>";

        return $observe;
    } // observe

    /**
     * url
     * This takes a string and makes an URL
     * @param string $action
     */
    public static function url($action): string
    {
        global $dic;

        return $dic->get(AjaxUriRetrieverInterface::class)->getAjaxUri() . $action;
    }

    /**
     * action
     * This takes the action, the source and the post (if passed) and
     * generates the full ajax link
     * @param string $action
     * @param string $source
     * @param string $post
     */
    public static function action($action, $source, $post = ''): string
    {
        $url = self::url($action);

        $non_quoted = array('document', 'window');

        if (in_array($source, $non_quoted)) {
            $source_txt = $source;
        } else {
            $source_txt = "'$source'";
        }

        if ($post) {
            $ajax_string = "ajaxPost('$url', '$post', $source_txt)";
        } else {
            $ajax_string = "ajaxPut('$url', $source_txt)";
        }

        return $ajax_string;
    } // action

    /**
     * button
     * This prints out an img of the specified icon with the specified alt
     * text and then sets up the required ajax for it.
     * @param string $action
     * @param string $icon
     * @param string $alt
     * @param string $source
     * @param string $post
     * @param string $class
     * @param string $confirm
     */
    public static function button($action, $icon, $alt, $source = '', $post = '', $class = '', $confirm = ''): string
    {
        // Get the correct action
        $ajax_string = self::action($action, $source, $post);

        // If they passed a span class
        if ($class) {
            $class = ' class="' . $class . '"';
        }

        $string = Ui::get_icon($icon, $alt);

        // Generate an <a> so that it's more compliant with older
        // browsers (ie :hover actions) and also to unify linkbuttons
        // (w/o ajax) display
        $string = "<a href=\"javascript:void(0);\" id=\"$source\" $class>" . $string . "</a>\n";

        $string .= self::observe($source, 'click', $ajax_string, $confirm);

        return $string;
    } // button

    /**
     * button_with_text
     * This prints out an img of the specified icon coupled with
     * the text string and then sets up the required ajax for it.
     * @param string $action
     * @param string $icon
     * @param string $text
     * @param string $source
     * @param string $post
     * @param string $class
     * @param string $confirm
     */
    public static function button_with_text($action, $icon, $text, $source = '', $post = '', $class = '', $confirm = ''): string
    {
        // Get the correct action
        $ajax_string = self::action($action, $source, $post);

        // If they passed a span class
        if ($class) {
            $class = ' class="' . $class . '"';
        }

        $button = Ui::get_icon($icon, $text);

        $string = "<a href=\"javascript:void(0);\" id=\"$source\" $class>" . $button . " " . $text . "</a>\n";

        $string .= self::observe($source, 'click', $ajax_string, $confirm);

        return $string;
    } // button

    /**
     * text
     * This prints out the specified text as a link and sets up the required
     * ajax for the link so it works correctly
     * @param string $action
     * @param string $text
     * @param string $source
     * @param string $post
     * @param string $class
     */
    public static function text($action, $text, $source, $post = '', $class = ''): string
    {
        // Temporary workaround to avoid sorting on custom base requests
        if (!defined("NO_BROWSE_SORTING") || strpos($source, "sort_") === false) {
            // Avoid duplicate id
            $source .= '_' . time() . '_' . self::$counter++;

            // Format the string we wanna use
            $ajax_string = self::action($action, $source, $post);

            // If they passed a span class
            if ($class) {
                $class = ' class="' . $class . '"';
            }

            $string = "<a href=\"javascript:void(0);\" id=\"$source\" $class>$text</a>\n";

            $string .= self::observe($source, 'click', $ajax_string);
        } else {
            $string = $text;
        }

        return $string;
    } // text

    /**
     * run
     * This runs the specified action no questions asked
     * @param string $action
     */
    public static function run($action): void
    {
        echo "<script><!--\n";
        echo "$action";
        echo "\n--></script>";
    } // run

    /**
     * set_include_override
     * This sets the including div override, used only one place. Kind of a
     * hack.
     * @param bool $value
     */
    public static function set_include_override($value): void
    {
        self::$include_override = make_bool($value);
    } // set_include_override

    /**
     * start_container
     * This checks to see if we're AJAXin'. If we aren't then it echoes out
     * the html needed to start a container that can be replaced by Ajax.
     * @param string $name
     * @param string $class
     */
    public static function start_container($name, $class = ''): bool
    {
        if (defined('AJAX_INCLUDE') && !self::$include_override) {
            return true;
        } else {
            echo '<div id="' . scrub_out($name) . '"';
            if (!empty($class)) {
                echo ' class="' . scrub_out($class) . '"';
            }
            echo '>';
        }

        return false;
    } // start_container

    /**
     * end_container
     * This ends the container if we're not doing the AJAX thing
     */
    public static function end_container(): bool
    {
        if (defined('AJAX_INCLUDE') && !self::$include_override) {
            return true;
        } else {
            echo "</div>";
            self::$include_override = false;
        }

        return false;
    } // end_container
} // end ajax.class
