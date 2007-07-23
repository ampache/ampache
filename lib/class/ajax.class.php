<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
	public static function observe($source,$method,$action) { 

                $observe	= "<script type=\"text/javascript\">\n";
                $observe	.= "\tEvent.observe('$source','$method',function(){" . $action . ";});\n";
                $observe	.= "</script>\n";

		return $observe; 

	} // observe

	/**
	 * button
	 * This prints out an img of the specified icon with the specified alt text
	 * and then sets up the required ajax for it
	 */
	public static function button($action,$icon,$alt,$source='',$post='') { 

                $url = Config::get('ajax_url') . $action;

                // Define the Action that is going to be performed
                if ($post) {
                        $ajax_string = "ajaxPost('$url','$post','$source')";
                }
                else {
                        $ajax_string = "ajaxPut('$url','$source')";
                }

		$string = get_user_icon($icon,$alt,$source); 

		$string .= self::observe($source,'click',$ajax_string); 

                return $string;

	} // button

	/**
	 * text
	 * This prints out the specified text as a link and setups the required
	 * ajax for the link so it works correctly
	 */
	public static function text($action,$text,$source,$post='',$span_class='') { 

		$url = Config::get('ajax_url') . $action; 

		// Use ajaxPost() if we are doing a post
		if ($post) { 
			$ajax_string = "ajaxPost('$url','$post','$source')"; 
		}
		else { 
			$ajax_string = "ajaxPut('$url','$source')"; 
		} 

		// If they passed a span class
		if ($span_class) { 
			$class_txt = ' class="' . $span_class . '"'; 
		} 

		// If we pass a source put it in the ID
		$string = "<span id=\"$source\" $class_txt>$text</span>\n"; 

		$string .= self::observe($source,'click',$ajax_string); 

		return $string; 

	} // text

} // end Ajax class
?>
