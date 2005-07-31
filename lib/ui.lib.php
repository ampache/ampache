<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 *	UI Function Library
 *	This contains functions that are generic, and display information
 *	things like a confirmation box, etc and so forth
 *	@package Web Interface
 * 	@catagory Library
 */

/**
 *  show_confirmation
 * shows a confirmation of an action
 *	@param $next_url	Where to go next
 *	@param $title		The Title of the message
 *	@param $text		The details of the message
 */
function show_confirmation($title,$text,$next_url) {
	
	if (substr_count($next_url,conf('web_path'))) { 
		$path = $next_url;
	}
	else {
		$path = conf('web_path') . "/$next_url";
	}

	require (conf('prefix') . "/templates/show_confirmation.inc.php");

} // show_confirmation

/**
 * set_preferences
 * legacy function...
 * @todo Remove References
 * @deprecated 
 */
function set_preferences() { 

	get_preferences();
	return true;

} // set_preferences

/**
 *  get_preferences
 * reads this users preferences
 */
function get_preferences($username=0) {

	/* Get System Preferences first */
	$sql = "SELECT preferences.name,user_preference.value FROM preferences,user_preference WHERE user_preference.user='0' " .
		" AND user_preference.preference = preferences.id AND preferences.type='system'";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_object($db_results)) { 
		$results[$r->name] = $r->value;
	} // end while sys prefs

	conf($results, 1);

	unset($results);
	
	if (!$username) { $username = $_SESSION['userdata']['username']; }

	$user = new User($username);

	$sql = "SELECT preferences.name,user_preference.value FROM preferences,user_preference WHERE user_preference.user='$user->username'" .
		" AND user_preference.preference=preferences.id";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_object($db_results)) { 
		$results[$r->name] = $r->value;
	}

	unset($results['user'], $results['id']);

	conf($results, 1);

} // get_preferences

/**
 *  flip_class
 * takes an array of 2 class names
 *		and flips them back and forth and
 *		then echo's out [0]
 */
function flip_class($array=0) {

	static $classes = array();

	if ($array) { 
		$classes = $array;
	}
	else {
		$classes = array_reverse($classes);
		return $classes[0];
	}

} // flip_class

/**
 *  clear_now_playing
 * Clears the now playing information incase something has
 *		gotten stuck in there
 */
function clear_now_playing() { 

	$sql = "DELETE FROM now_playing";
	$db_results = mysql_query($sql, dbh());
	
	return true;

} // clear_now_playing

/**
 *  show_tool_box
 * shows the toolbox
 */
function show_tool_box ($title, $items) {

        include(conf('prefix') . "/templates/tool_box.inc");
	
}// show_tool_box

/**
 *  show_box
 * shows a generic box
 */
function show_box($title,$items) { 

	include(conf('prefix') . "/templates/show_box.inc");

} // show_box

/**	
 *  show_menu_items
 * shows menu items
 */
function show_menu_items ($high) {

        include(conf('prefix') . "/templates/menu.inc");
	
} // show_menu_items

/** 
 * Show Browse Menu
 * Shows the menu used by the browse page
 * @package Web Interface
 * @cataogry Menu
 * @author Karl Vollmer
 */
function show_browse_menu($highlight) { 

	include(conf('prefix'). "/templates/show_browse_menu.inc");

} // show_browse_menu

/**
 *  _
 * checks to see if the alias _ is defined
 *	if it isn't it defines it as a simple return
 */
if (!function_exists('_')) { 
	function _($string) { 

		return $string;

	} // _
} // if _ isn't defined

/**
 *  show_playlist_menu
 * playlist functions
 */
function show_playlist_menu () {

        echo "<br /><span class=\"header2\">" . _("Playlist Actions") . ": <a href=\"" . conf('web_path') . "/playlist.php?action=new\">" . _("New") ."</a> | ";
        echo "<a href=\"" . conf('web_path') . "/playlist.php\"> " . _("View All") . "</a> | ";
	echo "<a href=\"" . conf('web_path') . "/playlist.php?action=show_import_playlist\"> " . _("Import") . "</a>";
        echo "</span><br /><br />";
	
} // show_playlist_menu

/**
 *  show_admin_menu
 * shows the admin menu
 */
function show_admin_menu ($admin_highlight) {
        include(conf('prefix') . "/templates/admin_menu.inc");
} // show_admin_menu

/**
 *  access_denied
 * throws an error if they try to do something
 * 	that they aren't allowed to
 */
function access_denied() { 

	show_template('style');
	show_footer();
	echo "<br /><br /><br />";
        echo "<div class=\"fatalerror\">Error Access Denied</div>\n";
	show_footer();
	exit();

} // access_denied

/**
 *  show_users
 * shows all users (admin function)
 */
function show_users () {

        $dbh = dbh();

        // Setup the View Ojbect
        $view = new View();
        $view->import_session_view();

        // if we are returning
        if ($_REQUEST['keep_view']) { 
                $view->initialize();
        }
        // If we aren't keeping the view then initlize it
        else {
        	$sql = "SELECT username FROM user";
                $db_results = mysql_query($sql, $dbh);
                $total_items = mysql_num_rows($db_results);
                if ($match != "Show_all") { $offset_limit = $_SESSION['userdata']['offset_limit']; }
                $view = new View($sql, 'admin/users.php','fullname',$total_items,$offset_limit); 
        } 

        $db_result = mysql_query($view->sql, $dbh);

        require(conf('prefix') . "/templates/show_users.inc");

} // show_users()


/**
 *  return_referer
 * returns the script part of the 
 *	referer address passed by the web browser
 *	this is not %100 accurate
 */
function return_referer() { 

	$web_path = substr(conf('web_path'),0,strlen(conf('web_path'))-1-strlen($_SERVER['SERVER_PORT'])) . "/";
	$next = str_replace($web_path,"",$_SERVER['HTTP_REFERER']);

	// If there is more than one :// we know it's fudged
	// and just return the index
	if (substr_count($next,"://") > 1) { 
		return "index.php";
	}

	return $next;

} // return_referer

/**
 *  show_alphabet_list
 * shows the A-Z,0-9 lists for 
 *		albums and artist pages
 */
function show_alphabet_list ($type,$script="artist.php",$selected="false") {

        $list = array(A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,1,2,3,4,5,6,7,8,9,"0");

        $style_name = "style_" . strtolower($selected);
	${$style_name} = "style=\"font-weight:bold;\"";

        echo "<div class=\"alphabet\">";
        foreach ($list as $l) {
		$style_name = "style_" . strtolower($l);
                echo "<a href=\"". conf('web_path') ."/$script?action=match&amp;match=$l\" " . ${$style_name} . ">$l</a> | \n";
        }
	
	echo " <a href=\"". conf('web_path') ."/$script?action=match&amp;match=Browse\" $style_browse>" . _("Browse") . "</a> | \n";
        if ($script == "albums.php") {
                echo " <a href=\"". conf('web_path') ."/$script?action=match&amp;match=Show_missing_art\" $style_show_missing_art>" . _("Show w/o art") . "</a> | \n";
        } // if we are on the albums page
	
        echo " <a href=\"". conf('web_path') ."/$script?action=match&amp;match=Show_all\" $style_show_all>" . _("Show all") . "</a>";
	
        echo "</div>\n";
} // show_alphabet_list

/**
 *  show_local_control
 * shows the controls
 *	for localplay
 */
function show_local_control () {

        require_once(conf('prefix') . "/templates/show_localplay.inc");

} // show_local_control

/**
 *  truncate_with_ellipse
 * truncates a text file to specified length by adding
 *	thre dots (ellipse) to the end
 *	(Thx Nedko Arnaudov)
 * @todo Fix Spelling!
 * @depreciated
 */
function truncate_with_ellipse($text, $max=27) {

	/* Run the function with the correct spelling */
	return truncate_with_ellipsis($text,$max);

} // truncate_with_ellipse

/** 
 * truncate_with_ellipsis
 * Correct Spelling function that truncates text to a specific lenght
 * and appends three dots, or an ellipsis to the end
 * @package Web Interface
 * @catagory General
 * @author Nedko Arnaudov
 */
function truncate_with_ellipsis($text, $max=27) { 

        /* If we want it to be shorter than three, just throw it back */
        if ($max > 3) {

                /* Make sure the functions exist before doing the iconv mojo */
                if (function_exists('iconv') && function_exists('iconv_substr') && function_exists('iconv_strlen')) {
                        if (iconv_strlen($text, conf('site_charset')) > $max) {
                                $text = iconv_substr($text, 0, $max-3, conf('site_charset'));
                                $text .= iconv("ISO-8859-1", conf('site_charset'), "...");
                        }
                }

                /* Do normal substr if we don't have iconv */
                else {
                        if (strlen($text) > $max) {
                                $text = substr($text,0,$max-3)."...";
                        }
                } // else no iconv
        } // else greater than 3

        return $text;

} // truncate_with_ellipsis

/**
 *  show_footer
 * shows the footer of the page
 */
function show_footer() {
        $class = "table-header";
        echo "<br /><br /><br /><div class=\"$class\" style=\"border: solid thin black;\">&nbsp;</div>";
} // show_footer

/**
 *  show_now_playing
 * shows the now playing template
 */
function show_now_playing() { 

        $dbh = dbh();
        $web_path = conf('web_path');
	$results = get_now_playing();
        require (conf('prefix') . "/templates/show_now_playing.inc");

} // show_now_playing

/**
 *  show_user_registration
 * this function is called for a new user
 * registration
 * @author Terry
 * @todo Fix so that it recieves an array of values for the user reg rather than seperate
 */
function show_user_registration ($values=array()) { 

	require (conf('prefix') . "/templates/show_user_registration.inc.php");

} // show_user_registration

/**
 * show_edit_profile
 * shows a single user profile for editing
 * @package Web Interface
 * @catagory Display
 */
function show_edit_profile($username) { 

	$this_user = new User($username);

	require (conf('prefix') . "/templates/show_user.inc.php");
	
} // show_edit_profile

/**
 *  show_playlist
 * this shows the current playlist
 */
function show_playlist($playlist_id) { 

	/* Create the Playlist */
	$playlist = new Playlist($playlist_id);
        $song_ids = $playlist->get_songs();

        if (count($song_ids) > 0) {
                show_songs($song_ids, $playlist->id);
        }
        else {
                echo "<p>" . _("No songs in this playlist.") . "</p>\n";
        }

} // show_playlist

/**
 *  show_play_selected
 * this shows the playselected/add to playlist 
 *	box, which includes a little javascript
 */
function show_play_selected() { 

	require (conf('prefix') . "/templates/show_play_selected.inc.php");

} // show_play_selected

/**
 *  get_now_playing
 * gets the now playing information
 * @package Web Interface
 * @catagory Get
 */
function get_now_playing() {

	$sql = "SELECT song_id,user FROM now_playing ORDER BY start_time DESC";
	$db_results = mysql_query($sql, dbh());
	while ($r = mysql_fetch_assoc($db_results)) { 
		$song = new Song($r['song_id']);
		$song->format_song();
		$np_user = new User($r['user']);
		$results[] = array('song'=>$song,'user'=>$np_user);
	} // end while

	$myMpd = init_mpd();

	if (is_object($myMpd) AND conf('mpd_method') == 'file') { 
		$sql = "SELECT song.id FROM song WHERE file = \"". conf('mpd_dir') . "/" . 
			$myMpd->playlist[$myMpd->current_track_id]['file']. "\"";

	        $db_results = @mysql_query($sql,dbh());

	        while ($r = mysql_fetch_assoc($db_results)) {

	                $song = new Song($r['id']);
			$song->format_song();
			$np_user = new User(0);
			$np_user->fullname = 'MPD User';
			$np_user->username = 'mpd_user';
			$results[] = array('song'=>$song,'user'=>$np_user);

		} // end while

	} // end if we have a MPD object

	
	return $results;

} // get_now_playing

/**
 *  show_clear
 * this is a hack because of the float mojo it clears the floats
 * @package Web Interface
 * @catagory Hack-o-Rama
 * @author Karl Vollmer
 */
function show_clear() { 

	echo "\n<br style=\"clear:both;\" />\n";

} // show_clear

/**
 *	show_page_footer
 *	adds page footer including html and body end tags
 *	@param $menu			menu item to highlight
 *	@param $admin_menu		admin menu item to highlight
 *	@param $display_menu		display menu or not (1 on 0 off) 
 * 	@package Web Interface
 * 	@catagory Display
 */
function show_page_footer($menu="Home", $admin_menu='', $display_menu=0) {

	if ($display_menu){
		if($menu == 'Admin'){
			show_admin_menu($admin_menu);
		} // end if admin

		show_menu_items($menu);
		
	} // end if

	show_template('footer');

} // show_page_footer

/**
 * 	Show All Popular
 * 	This functions shows all of the possible global popular tables, this is basicly a top X where X is 
 * 	set on a per user basis
 *	@package Web Interface
 *	@catagory Display
 *	@author Karl Vollmer
 */
function show_all_popular() { 

	$artists 	= get_global_popular('artist');
	$albums		= get_global_popular('album');
	$songs		= get_global_popular('song');
	$genres		= get_global_popular('genre');

	require_once(conf('prefix') . '/templates/show_all_popular.inc.php');

} // show_all_popular

/** 
 * 	Show All Recent
 * 	This function shows all of the possible "Newest" tables. The number of newest is pulled from the users
 * 	popular threshold
 *	@package Web Interface
 *	@catagory Display
 *	@author Karl Vollmer
 */
function show_all_recent() { 


} // show_all_recent

/**
 * show_local_catalog_info
 * Shows the catalog stats 
 * @package Web INterface
 * @catagory Display
 */
function show_local_catalog_info() {

        $dbh = dbh();
	
	/* Before we display anything make sure that they have a catalog */
	$query = "SELECT * FROM catalog";
	$db_results = mysql_query($query, $dbh);
	if (!mysql_num_rows($db_results)) { 
		$items[] = "<span align=\"center\" class=\"error\">" . _("No Catalogs Found!") . "</span><br />";
		$items[] = "<a href=\"" . conf('web_path') . "/admin/catalog.php?action=show_add_catalog\">" ._("Add a Catalog") . "</a>";
		show_info_box(_("Catalog Statistics"),'catalog',$items);
		return false;
	}

        $query = "SELECT count(*) AS songs, SUM(size) AS size, SUM(time) as time FROM song";
        $db_result = mysql_query($query, $dbh);
        $songs = mysql_fetch_assoc($db_result);

        $query = "SELECT count(*) FROM album";
        $db_result = mysql_query($query, $dbh);
        $albums = mysql_fetch_row($db_result);

        $query = "SELECT count(*) FROM artist";
        $db_result = mysql_query($query, $dbh);
        $artists = mysql_fetch_row($db_result);

        $sql = "SELECT count(*) FROM user";
        $db_result = mysql_query($sql, $dbh);
        $users = mysql_fetch_row($db_result);

        $time = time();
        $last_seen_time = $time - 1200;
        $sql =  "SELECT count(DISTINCT s.username) FROM session AS s " .
                "INNER JOIN user AS u ON s.username = u.username " .
                "WHERE s.expire > " . $time . " " .
                "AND u.last_seen > " . $last_seen_time;
        $db_result = mysql_query($sql, $dbh);
        $connected_users = mysql_fetch_row($db_result);

        $hours = floor($songs['time']/3600);
        $size = $songs['size']/1048576;

        $days = floor($hours/24);
        $hours = $hours%24;

        $time_text = "$days ";
        $time_text .= ($days == 1) ? _("day") : _("days");
        $time_text .= ", $hours ";
        $time_text .= ($hours == 1) ? _("hour") : _("hours");

        if ( $size > 1024 ) {
                $total_size = sprintf("%.2f", ($size/1024));
                $size_unit = "GB";
        }
        else {
                $total_size = sprintf("%.2f", $size);
                $size_unit = "MB";
        }

	require(conf('prefix') . "/templates/show_local_catalog_info.inc.php");
	
} // show_local_catalog_info

/*!
	@function img_resize
	@discussion this automaticly resizes the image for thumbnail viewing
		only works on gif/jpg/png this function also checks to make
		sure php-gd is enabled
*/
function img_resize($image,$size,$type){

	/* Make sure they even want us to resize it */
	if (!conf('resize_images')) { 
		return false;
	}

	/* First check for php-gd */
	$info = gd_info();

	if ($type == 'jpg' AND !$info['JPG Support']) { 
		return false; 
	}
	elseif ($type == 'png' AND !$info['PNG Support']) { 
		return false;
	}
	elseif ($type == 'gif' AND !$info['GIF Create Support']) { 
		return false;
	}

        $src = imagecreatefromstring($image);
        $width = imagesx($src);
        $height = imagesy($src);

        $new_w = $size['width'];
        $new_h = $size['height'];
        
	$img = imagecreatetruecolor($new_w,$new_h);
        imagecopyresampled($img,$src,0,0,0,0,$new_w,$new_h,$width,$height);
        
        // determine image type and send it to the client
	switch ($type) { 
		case 'jpg':
		        imagejpeg($img,null,100);
		break;
		case 'gif':
			imagegif($img,null,100);
		break;
		case 'png':
			imagepng($img,null,100);
		break;
	}

} // img_resize


?>
