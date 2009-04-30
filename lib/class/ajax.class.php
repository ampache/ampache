<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * Ajax class
 * This class is specifically for setuping/printing out ajax related
 * elements onto a page it takes care of the observing and all that raz-a-ma-taz
 */
class Ajax { 

	private static $include_override; 

	/**
	 * constructor
	 * This is what is called when the class is loaded
	 */
	public function __construct() { 

		// Rien a faire

	} // constructor

	/**
	 * observe
	 * This returns a string with the correct and full ajax 'observe' stuff from prototype
	 */
	public static function observe($source,$method,$action,$post='') { 

                $non_quoted = array('document','window');

                if (in_array($source,$non_quoted)) {
                        $source_txt = $source;
                }
                else {
                        $source_txt = "'$source'";
                }

		// If it's a post then we need to stop events
		if ($post) { 
			$action  = 'Event.stop(e); ' . $action; 
		} 

                $observe	= "<script type=\"text/javascript\">";
                $observe	.= "Event.observe($source_txt,'$method',function(e){" . $action . ";});";
                $observe	.= "</script>";

		return $observe; 

	} // observe

	/**
	 * action
	 * This takes the action, the source and the post (if passed) and generated the full
	 * ajax link
	 */
	public static function action($action,$source,$post='') { 

		$url = Config::get('ajax_url') . $action; 

                $non_quoted = array('document','window');

                if (in_array($source,$non_quoted)) {
                        $source_txt = $source;
                }
                else {
                        $source_txt = "'$source'";
                }
    
		if ($post) { 
			$ajax_string = "ajaxPost('$url','$post',$source_txt)"; 
		}
		else { 
			$ajax_string = "ajaxPut('$url',$source_txt)"; 
		} 
	
		return $ajax_string; 

	} // action

	/**
	 * button
	 * This prints out an img of the specified icon with the specified alt text
	 * and then sets up the required ajax for it
	 */
	public static function button($action,$icon,$alt,$source='',$post='',$class='') { 

		// Get the correct action
		$ajax_string = self::action($action,$source,$post); 

    		// If they passed a span class
		if ($class) { 
			$class_txt = ' class="' . $class . '"'; 
		} 


		$string = get_user_icon($icon,$alt); 

    		// Generate a <a> so that it's more compliant with older browsers
		// (ie :hover actions) and also to unify linkbuttons (w/o ajax) display
		$string = "<a href=\"javascript:void(0);\" id=\"$source\" $class_txt>".$string."</a>\n"; 

		$string .= self::observe($source,'click',$ajax_string); 

		return $string;

	} // button

	/**
	 * text
	 * This prints out the specified text as a link and setups the required
	 * ajax for the link so it works correctly
	 */
	public static function text($action,$text,$source,$post='',$class='') { 

		// Format the string we wanna use
		$ajax_string = self::action($action,$source,$post); 

		// If they passed a span class
		if ($class) { 
			$class_txt = ' class="' . $class . '"'; 
		} 

		// If we pass a source put it in the ID
    		$string = "<a href=\"javascript:void(0);\" id=\"$source\" $class_txt>$text</a>\n"; 

		$string .= self::observe($source,'click',$ajax_string); 

		return $string; 

	} // text

	/**
	 * run
	 * This runs the specified action no questions asked
	 */
	public static function run($action) { 

		echo "<script type=\"text/javascript\"><!--\n"; 
		echo "$action"; 
		echo "\n--></script>"; 

	} // run

	/**
 	 * set_include_override
	 * This sets the cinlduing div override, used only one place kind of a hack
	 */
	public static function set_include_override($value) { 

		self::$include_override = make_bool($value); 

	} // set_include_override

	/**
 	 * start_container
	 * This checks to see if we're AJAX'in if we aren't then it echos out the 
	 * html needed to start a container that can be replaced by Ajax
	 */
	public static function start_container($name) { 

		if (AJAX_INCLUDE == '1' AND !self::$include_override) { return true; } 

		echo '<div id="' . scrub_out($name) . '">'; 

	} // start_container

	/**
	 * end_container
	 * This ends the container if we're not doing the AJAX thing
	 */
	public static function end_container() { 

		if (AJAX_INCLUDE == '1' AND !self::$include_override) { return true; } 

		echo "</div>"; 

		self::$include_override = false; 

	} // end_container

} // end Ajax class
?>
