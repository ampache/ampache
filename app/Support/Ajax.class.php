<?php

/* 
 * This class is an helper to quickly migrate from Ampache legacy to Lavarel
 * IT SHOULD BE REMOVED SOON to have a better code design
 */

class Ajax {
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
     * @return string
     */
    public static function observe($source, $method, $action, $confirm='')
    {
        $non_quoted = array('document', 'window');

        if (in_array($source,$non_quoted)) {
            $source_txt = $source;
        } else {
            $source_txt = "'#$source'";
        }

        $observe   = "<script type=\"text/javascript\">";
        $methodact = (($method == 'click') ? "update_action();" : "");
        if (Config::get('theme.ajax_load') && $method == 'load') {
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
     * @return string
     */
    public static function url($action)
    {
        return url('server/ajax/' . $action);
    }

    /**
     * action
     * This takes the action, the source and the post (if passed) and
     * generates the full ajax link
     * @param string $action
     * @param string $source
     * @param string $post
     * @return string
     */
    public static function action($action, $source, $post='')
    {
        $url = self::url($action);

        $non_quoted = array('document','window');

        if (in_array($source,$non_quoted)) {
            $source_txt = $source;
        } else {
            $source_txt = "'$source'";
        }

        if (!empty($post)) {
            $ajax_string = "ajaxPost('$url','$post',$source_txt)";
        } else {
            $ajax_string = "ajaxPut('$url',$source_txt)";
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
     * @return string
     */
    public static function button($action, $icon, $alt, $source='', $post='', $class='', $confirm='')
    {
        // Get the correct action
        $ajax_string = self::action($action, $source, $post);

        // If they passed a span class
        if ($class) {
            $class = ' class="' . $class . '"';
        }

        $string = "<img src='" . url_icon($icon) . "' alt='" . $alt . "'/>";

        // Generate an <a> so that it's more compliant with older
        // browsers (ie :hover actions) and also to unify linkbuttons
        // (w/o ajax) display
        $string = "<a href=\"javascript:void(0);\" id=\"$source\" $class>" . $string . "</a>\n";

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
     * @return string
     */
    public static function text($action, $text, $source, $post='', $class='')
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
    public static function run($action)
    {
        echo "<script type=\"text/javascript\"><!--\n";
        echo "$action";
        echo "\n--></script>";
    } // run

    /**
      * set_include_override
     * This sets the including div override, used only one place. Kind of a
     * hack.
     * @param bool $value
     */
    public static function set_include_override($value)
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
    public static function start_container($name, $class = '')
    {
        if (defined('AJAX_INCLUDE') && !self::$include_override) {
            return true;
        }

        echo '<div id="' . $name . '"';
        if (!empty($class)) {
            echo ' class="' . $class . '"';
        }
        echo '>';
    } // start_container

    /**
     * end_container
     * This ends the container if we're not doing the AJAX thing
     */
    public static function end_container()
    {
        if (defined('AJAX_INCLUDE') && !self::$include_override) {
            return true;
        }

        echo "</div>";

        self::$include_override = false;
    } // end_container
    
    /**
     * success
     * This create a default success response
     */
    public static function success()
    {
        return ['success' => ''];
    }
    
    /**
     * failure
     * This create a default failure response
     */
    public static function failure($message = '')
    {
        return ['error' => $message];
    }
}
