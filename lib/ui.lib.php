<?php
/*

   Copyright (c) 2001 - 2006 Ampache.org
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
 * $next_url	Where to go next
 * $title	The Title of the message
 * $text	The details of the message
 * $cancel	T/F show a cancel button that uses return_referrer()
 */
function show_confirmation($title,$text,$next_url,$cancel=0) {

	if (substr_count($next_url,conf('web_path'))) {
		$path = $next_url;
	}
	else {
		$path = conf('web_path') . "/$next_url";
	}

	require (conf('prefix') . "/templates/show_confirmation.inc.php");

} // show_confirmation

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

	$highlight = ucfirst($highlight);

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

	echo "<br /><br /><br />";
	echo "<div class=\"fatalerror\">" . _("Error Access Denied") . "</div>\n";
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
	// wow this is stupid 
	$GLOBALS['view'] = $view;
	require(conf('prefix') . "/templates/show_users.inc");

} // show_users()


/**
 *  return_referer
 * returns the script part of the referer address passed by the web browser
 * this is not %100 accurate. Also because this is not passed by us we need
 * to clean it up, take the filename then check for a /admin/ and dump the rest
 */
function return_referer() {

	$referer = $_SERVER['HTTP_REFERER'];

	$file = basename($referer);
	
	/* Strip off the filename */
	$referer = substr($referer,0,strlen($referer)-strlen($file));
	
	if (substr($referer,strlen($referer)-6,6) == 'admin/') { 
		$file = 'admin/' . $file;
	}

	return $file;

} // return_referer

/**
 *  show_alphabet_list
 * shows the A-Z,0-9 lists for
 *		albums and artist pages
 */
function show_alphabet_list ($type,$script="artist.php",$selected="false",$action='match') {

	$list = array(A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,1,2,3,4,5,6,7,8,9,"0");

	$style_name = "style_" . strtolower($selected);
	${$style_name} = "style=\"font-weight:bold;\"";
	unset($title);
	echo "<div class=\"alphabet\">";
	foreach ($list as $l) {
		$style_name = "style_" . strtolower($l);
		echo "<a href=\"". conf('web_path') ."/$script?action=$action&amp;match=$l\" " . ${$style_name} . ">$l</a> | \n";
	}

	echo " <a href=\"". conf('web_path') ."/$script?action=$action&amp;match=Browse\" $style_browse>" . _("Browse") . "</a> | \n";
	if ($script == "albums.php") {
		echo " <a href=\"". conf('web_path') ."/$script?action=$action&amp;match=Show_missing_art\" $style_show_missing_art>" . _("Show w/o art") . "</a> | \n";
	} // if we are on the albums page

	echo " <a href=\"". conf('web_path') ."/$script?action=$action&amp;match=Show_all\" $style_show_all>" . _("Show all") . "</a>";
	echo "</div>\n";

} // show_alphabet_list

/**
 * show_alphabet_form
 * this shows the spiffy little form that acts as a "quick search" when browsing
 * @package General
 * @catagory Display
 */
function show_alphabet_form($match, $text, $action) {

	require (conf('prefix') . '/templates/show_alphabet_form.inc.php');

} // show_alphabet_form


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

	require_once(conf('prefix') . '/templates/footer.inc');

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
/*
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
*/

	return $results;

} // get_now_playing

/**
 *  get_all_ratings() - Implemented by SoundOfEmotion
 *
 *  Concept design to show a user ALL of his ratings, and sort by
 *  highest to lowest (will be sortable by multiple fields later)
 *
 */

function get_all_ratings($rate_user,$sort_by) {;

	$sql       = "SELECT * FROM ratings WHERE user='$rate_user' AND object_type='$sort_by' ORDER BY user_rating DESC";
	$db_result = mysql_query( $sql, dbh() );

	while($row = mysql_fetch_assoc($db_result))
	{
		$type=$row['object_type'];
		$id=$row['object_id'];
		$rating=$row['user_rating'];
		$art_image="<img border=\"0\" src=\"" . conf('web_path') . "/albumart.php?id=" . $id . "\" alt=\"Album Art\" height=\"100\" />";
		$art_link="<a href='http://" . conf('web_path') . "/ampache/albums.php?action=show&album=$id'>$art_image</a>";
		$artist_name=$album->f_artist;
		$album_name=$album->name;
		if($type=="album"){
			echo ("<table width=400>" .
					"<tr>" .
					"<td width=100 align=center>$artLink</td>" .
					"<td width=* align=left>".ucfirst($type)." #$id<br>" .
					"Rating: $rating</td>" .
					"</tr>" .
					"</table>");
		}
		else{
			$artistLink="<a href='" . conf('web_path') . "/ampache/artists.php?action=show&artist=$id'>Artist $id</a>";
			echo ("<table width=150>" .
					"<tr>" .
					"<td align=left>$artist_link<br>" .
					"Rating: $rating" .
					"</td>" .
					"</tr>" .
					"</table>");
		}

	}

} // get_artist_rating()

/*
 * Artist Ratings - Implemented by SoundOfEmotion
 *
 * set_artist_rating()
 *
 * check to see if the ratings exist
 * if they do: update them
 * if they don't: insert them
 *
 */

function set_artist_rating($artist_id, $rate_user, $rating) {
	$artist_id = sql_escape($artist_id);

	$sql	     = "SELECT * FROM ratings WHERE user='$rate_user' AND object_type='artist' AND object_id='$artist_id'";
	$db_result = mysql_query( $sql, dbh() );
	$r         = mysql_fetch_row( $db_result );

	if($r[0]) {
		$sql2       = "UPDATE ratings SET user_rating='$rating' WHERE object_id='$artist_id' AND user='$rate_user' AND object_type='artist'";
		$db_result2 = mysql_query( $sql2, dbh() );
		$r          = mysql_fetch_row( $db_result2 );
		return mysql_insert_id( dbh() );
	}
	else if(!$r[0]) {
		$sql2       = "INSERT INTO ratings (id,user,object_type,object_id,user_rating) ".
			"VALUES ('','$rate_user','artist','$artist_id','$rating')";
		$db_result2 = mysql_query( $sql2, dbh() );
		return mysql_insert_id(dbh() );
	}
	else{
		return "NA";
	}
} // set_artist_rating()

/*
 * Album Ratings - Implemented by SoundOfEmotion
 *
 * set_album_rating()
 *
 * check to see if the ratings exist
 * if they do: update them
 * if they don't: insert them
 *
 */

function set_album_rating($album_id, $rate_user, $rating) {
	$album_id = sql_escape($album_id);

	$sql       = "SELECT * FROM ratings WHERE user='$rate_user' AND object_type='album' AND object_id='$album_id'";
	$db_result = mysql_query( $sql, dbh() );
	$r         = mysql_fetch_row( $db_result );

	if($r[0]) {
		$sql2       = "UPDATE ratings SET user_rating='$rating' WHERE object_id='$album_id' AND user='$rate_user' AND object_type='album'";
		$db_result2 = mysql_query( $sql2, dbh() );
		return mysql_insert_id( dbh() );
	}
	else if(!$r[0]) {
		$sql2       = "INSERT INTO ratings (id,user,object_type,object_id,user_rating) ".
			"VALUES ('','$rate_user','album','$album_id','$rating')";
		$db_result2 = mysql_query( $sql2, dbh() );
		return mysql_insert_id( dbh() );
	}
	else{
		return "NA";
	}
} // set_album_rating()

/*
 * Song Ratings - Implemented by SoundOfEmotion
 *
 * set_song_rating()
 *
 * check to see if the ratings exist
 * if they do: update them
 * if they don't: insert them
 *
 */

function set_song_rating($song_id, $rate_user, $rating) {
	$song_id = sql_escape($song_id);

	$sql       = "SELECT * FROM ratings WHERE user='$rate_user' AND object_type='song' AND object_id='$song_id'";
	$db_result = mysql_query( $sql, dbh() );
	$r         = mysql_fetch_row( $db_result );

	if($r[0]){
		$sql2       = "UPDATE ratings SET user_rating='$rating' WHERE object_id='$song_id' AND user='$rate_user' AND object_type='song'";
		$db_result2 = mysql_query( $sql2, dbh() );
		return mysql_insert_id( dbh() );
	}
	else if(!$r[0]){
		$sql2       = "INSERT INTO ratings (id,user,object_type,object_id,user_rating) ".
			"VALUES ('','$rate_user','song','$song_id','$rating')";
		$db_result2 = mysql_query( $sql2, dbh() );
		return mysql_insert_id( dbh() );
	}
	else{
		return "NA";
	}
} // set_song_rating()

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

	$artists	= get_newest('artist');
	$albums		= get_newest('album');

	require_once(conf('prefix') . '/templates/show_all_recent.inc.php');

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
		show_info_box(_('Catalog Statistics'),'catalog',$items);
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

	if (!function_exists('gd_info')) { return false; }

	/* First check for php-gd */
	$info = gd_info();

	if ( ($type == 'jpg' OR $type == 'jpeg') AND !$info['JPG Support']) {
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
		case 'jpeg':
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

/**
 * show_genres
 * this shows the 'many' genre form, it takes an array of genre objects and the view object
 * @package Genre
 * @catagory Display
 */
function show_genres($genres,$view) {

	require (conf('prefix') . '/templates/show_genres.inc.php');

} // show_genres

/**
 * show_genre
 * this shows a single genre item which is basicly just a link to the albums/artists/songs of said genre
 * @package Genre
 * @catagory Display
 */
function show_genre($genre_id) {

	$genre = new Genre($genre_id);

	require (conf('prefix') . '/templates/show_genre.inc.php');

} // show_genre

function show_random_play_bar() {

	require (conf('prefix') . '/templates/show_random_play_bar.inc.php');

} // show_random_play_bar()


/*
 * show_artist_pulldown()
 *
 * Helper functions for album and artist functions
 *
 */
function show_artist_pulldown ($artist_id,$select_name='artist') {

	$query = "SELECT id FROM artist ORDER BY name";
	$db_result = mysql_query($query, dbh());

	echo "\n<select name=\"$select_name\">\n";

	while ($r = mysql_fetch_assoc($db_result)) {

		$artist = new Artist($r['id']);
		$artist->get_count();

		if ( $artist_id == $r['id'] ) {
			echo "\t<option value=\"" . $artist->id . "\" selected=\"selected\">". scrub_out($artist->name) . "</option>\n";
		}
		else {
			echo "\t<option value=\"" . $artist->id . "\">". scrub_out($artist->name) ."</option>\n";
		}

	} // end while fetching artists

	echo "</select>\n";

} // show_artist_pulldown

/**
 * show_catalog_pulldown
 * This has been changed, first is the name of the
 * dropdown select, the second is the style to be applied
 *
 */
function show_catalog_pulldown ($name='catalog',$style) {

	$sql = "SELECT id,name FROM catalog ORDER BY name";
	$db_result = mysql_query($sql, dbh());

	echo "\n<select name=\"" . $name . "\" style=\"" . $style . "\">\n";

	echo "<option value=\"-1\">All</option>\n";

	while ($r = mysql_fetch_assoc($db_result)) {
		$catalog_name = scrub_out($r['name']);

		if ( $catalog == $r['id'] ) {
			echo "  <option value=\"" .$r['id'] . "\" selected=\"selected\">$catalog_name</option>\n";
		}
		else {
			echo "  <option value=\"" . $r['id'] . "\">$catalog_name</option>\n";
		}
	}
	echo "\n</select>\n";

} // show_catalog_pulldown


/**
 * show_submenu
 * This shows the submenu mojo for the sidebar, and I guess honestly anything
 * else you would want it to... takes an array of items which have ['url'] ['title']
 * and ['active']
 */
function show_submenu($items) {

	require (conf('prefix') . '/templates/subnavbar.inc.php');

} // show_submenu


/**
 * get_location
 * This function gets the information about said persons currently location
 * this is used for A) Sidebar highlighting & submenu showing and B) Titlebar information
 * it returns an array of information about what they are currently doing
 * Possible array elements
 * ['title']	Text name for the page
 * ['page']	actual page name
 * ['section']	name of the section we are in, admin, browse etc (submenu control)
 * @package General
 */
function get_location() {

	$location = array();

	if (strlen($_SERVER['PHP_SELF'])) { 
		$source = $_SERVER['PHP_SELF'];
	}
	else { 
		$source = $_SERVER['REQUEST_URI'];
	}

	/* Sanatize the $_SERVER['PHP_SELF'] variable */
	$source			= str_replace(conf('raw_web_path'),"",$source);
	$location['page'] 	= preg_replace("/^\/(.+\.php)\/?.*/","$1",$source);

	switch ($location['page']) {
		case 'index.php':
			$location['title'] 	= _('Home');
			break;
		case 'upload.php':
			$location['title'] 	= _('Upload');
			break;
		case 'localplay.php':
			$location['title'] 	= _('Local Play');
			break;
		case 'randomplay.php':
			$location['title'] 	= _('Random Play');
			break;
		case 'playlist.php':
			$location['title'] 	= _('Playlist');
			break;
		case 'search.php':
			$location['title'] 	= _('Search');
			break;
		case 'preferences.php':
			$location['title'] 	= _('Preferences');
			break;
		case 'admin/index.php':
			$location['title'] 	= _('Admin-Catalog');
			$location['section']	= 'admin';
			break;
		case 'admin/catalog.php':
			$location['title'] 	= _('Admin-Catalog');
			$location['section']	= 'admin';
			break;
		case 'admin/users.php':
			$location['title']	= _('Admin-User Management');
			$location['section']	= 'admin';
			break;
		case 'admin/mail.php':
			$location['title']	= _('Admin-Mail Users');
			$location['section']	= 'admin';
			break;
		case 'admin/access.php':
			$location['title']	= _('Admin-Manage Access Lists');
			$location['section']	= 'admin';
			break;
		case 'admin/preferences.php':
			$location['title']	= _('Admin-Site Preferences');
			$location['section']	= 'admin';
			break;
		case 'admin/modules.php':
			$location['title']	= _('Admin-Manage Modules');
			$location['section']	= 'admin';
			break;
		case 'browse.php':
			$location['title']	= _('Browse Music');
			$location['section']	= 'browse';
			break;
		case 'albums.php':
			$location['title']	= _('Albums');
			$location['section']	= 'browse';
			break;
		case 'artists.php':
			$location['title']	= _('Artists');
			$location['section']	= 'browse';
			break;
		case 'genre.php':
			$location['title']	= _('Genre');
			$location['section']	= 'browse';
			break;
		case 'stats.php':
			$location['title']	= _('Statistics');
			break;
		default:
			$location['title'] = '';
			break;
	} // switch on raw page location

	return $location;

} // get_location

/**
 * show_preference_box
 * This shows the preference box for the preferences pages
 * it takes a chunck of the crazy preference array and then displays it out
 * it does not contain the <form> </form> tags
 */
function show_preference_box($preferences) { 
	
	include (conf('prefix') . '/templates/show_preference_box.inc.php');

} // show_preference_box


/**
 * show_genre_pulldown
 * This shows a select of all of the genres, it takes the name of the select
 * the currently selected and then the size
 *
 */

function show_genre_pulldown ($name,$selected='',$size=1,$width=0,$style='') {

	/* Get them genre hippies */        
	$sql = "SELECT genre.id,genre.name FROM genre ORDER BY genre.name";
        $db_result = mysql_query($sql, dbh());

	if ($size > 0) { 
		$multiple_txt = "multiple=\"multiple\" size=\"$size\"";
	}
	if ($style) { 
		$style_txt = "style=\"$style\"";
	}

        echo "<select name=\"" . $name . "[]\" $multiple_txt $style_txt>\n";
	echo "\t<option value=\"-1\">" . _("All") . "</option>\n";

        while ($r = mysql_fetch_assoc($db_result)) {
		
		$r['name'] = scrub_out($r['name']);

		if ($width > 0) { 
			$r['name'] = truncate_with_ellipsis($r['name'],$width);
		}
		
                if ( $selected == $r['id'] ) {
                        echo "\t<option value=\"" . $r['id'] . "\" selected=\"selected\">" . $r['name'] . "</option>\n";
                }
                else {
                        echo "  <option value=\"" . $r['id'] . "\">" . $r['name'] . "</option>\n";
                }
        } // end while

        echo "</select>\n";

} // show_genre_pulldown

/**
 * good_email
 * Don't get me started... I'm sure the indenting is still wrong on this
 * it shouldn't be named this, it should be documented, yea this needs
 * some serious MOJO work
 */
function good_email($email) {
	// First check that there's one @ symbol, and that the lengths are good
	if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
		// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
		return false;
	}

	// Split it into sections
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
				return false;
			}
		}
	if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
			return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
				return false;
			}
		}
	}
	return true;
} //good_email

/**
 * str_rand
 *
 *
 */
function str_rand($length = 8, $seeds = 'abcdefghijklmnopqrstuvwxyz0123456789'){
    $str = '';
    $seeds_count = strlen($seeds);

    // Seed
    list($usec, $sec) = explode(' ', microtime());
    $seed = (float) $sec + ((float) $usec * 100000);
    mt_srand($seed);

    // Generate
    for ($i = 0; $length > $i; $i++) {
        $str .= $seeds{mt_rand(0, $seeds_count - 1)};
    }

    return $str;
} //str_rand

/**
 * send_confirmation
 *
 *
 */
function send_confirmation($username, $fullname, $email, $password, $validation) {

$title = conf('site_title');
$from = "From: Ampache <".conf('mail_from').">";
$body = "Welcome to $title

Please keep this email for your records. Your account information is as follows: 

----------------------------
Username: $username
Password: $password
----------------------------

Your account is currently inactive. You cannot use it until you visit the following link:
"
. conf('web_path'). "/activate.php?mode=activate&u=$username&act_key=$validation

Please do not forget your password as it has been encrypted in our database and we cannot retrieve it for you. However, should you forget your password you can request a new one which will be activated in the same way as this account.

Thank you for registering.";


mail($email, "Welcome to $title" , $body, $from);

if (conf('admin_notify_reg')){

$admin_body = "A new user has registered at $title

The following values where entered;

Username: $username
Fullname: $fullname
E-Mail: $email

Click here to view user:
"
 . conf('web_path') . "/admin/users.php?action=edit&user=$username";



mail (conf('mail_from'), "New user registration at $title", $admin_body, $from);
}


} //send_confirmation

/**
 * show_registration_agreement
 * This function reads in /config/registration_agreement.php
 * Plaintext Only
 */
function show_registration_agreement() { 

	$filename = conf('prefix') . '/config/registration_agreement.php';

	/* Check for existance */
	$fp = fopen($filename,'r');

	if (!$fp) { return false; }

	$data = fread($fp,filesize($filename));

	/* Scrub and show */
	echo scrub_out($data);
		
} // show_registration_agreement


/**
 * show_playlist_import
 * This shows the playlist import templates
 */
function show_playlist_import() { 

	require (conf('prefix') . '/templates/show_import_playlist.inc.php');

} // show_playlist_import

/**
 * show_songs
 * Still not happy with this function, but at least it's in the right 
 * place now
 */
function show_songs ($song_ids, $playlist, $album=0) {

        $dbh = dbh();

        $totaltime = 0;
        $totalsize = 0;

        require (conf('prefix') . "/templates/show_songs.inc");

        return true;

} // show_songs

/**
 * show_album_select
 * This displays a select of every album that we've got in Ampache, (it can be hella long) it's used
 * by the Edit page, it takes a $name and a $album_id 
 */
function show_album_select($name='album',$album_id=0) { 

	echo "<select name=\"$name\">\n";

	$sql = "SELECT id, name, prefix FROM album ORDER BY name";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_assoc($db_results)) { 
		$selected = '';
		$album_name = trim($r['prefix'] . " " . $r['name']);
		if ($r['id'] == $album_id) { 
			$selected = "selected=\"selected\"";
		}

		echo "\t<option value=\"" . $r['id'] . "\" $selected>" . scrub_out($album_name) . "</option>\n";
		
	} // end while

	echo "</select>\n";

} // show_album_select

/**
 * show_artist_select
 * This is the same as the album select except it's *gasp* for artists how inventive!
 */
function show_artist_select($name='artist', $artist_id=0) { 

	echo "<select name=\"$name\">\n";
	
	$sql = "SELECT id, name, prefix FROM artist ORDER BY name";
	$db_results = mysql_query($sql, dbh());
	
	while ($r = mysql_fetch_assoc($db_results)) { 
		$selected = '';
		$artist_name = trim($r['prefix'] . " " . $r['name']);
		if ($r['id'] == $artist_id) { 
			$selected = "selected=\"selected\"";
		}

		echo "\t<option value=\"" . $r['id'] . "\" $selected>" . scrub_out($artist_name) . "</option>\n";

	} // end while

	echo "</select>\n";

} // show_artist_select

/**
 * show_genre_select
 * It's amazing we have three of these funtions now, this one shows a select of genres and take s name
 * and a selected genre... Woot!
 */
function show_genre_select($name='genre',$genre_id=0) { 

	echo "<select name=\"$name\">\n";

	$sql = "SELECT id, name FROM genre ORDER BY name";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_assoc($db_results)) { 
		$selected = '';
		$genre_name = $r['name'];
		if ($r['id'] == $genre_id) { 
			$selected = "selected=\"selected\"";
		}
	
		echo "\t<option value=\"" . $r['id'] . "\" $selected>" . scrub_out($genre_name) . "</option>\n";
	
	} // end while

	echo "</select>\n";

} // show_genre_select

/**
 * show_catalog_select
 * Yet another one of these buggers. this shows a drop down of all of your catalogs 
 */
function show_catalog_select($name='catalog',$catalog_id=0,$style='') { 

	echo "<select name=\"$name\" style=\"$style\">\n";

	$sql = "SELECT id, name FROM catalog ORDER BY name";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_assoc($db_results)) { 
		$selected = '';
		if ($r['id'] == $catalog_id) { 
			$selected = "selected=\"selected\"";
		}

		echo "\t<option value=\"" . $r['id'] . "\" $selected>" . scrub_out($r['name']) . "</option>\n";

	} // end while

	echo "</select>\n";

} // show_catalog_select


/**
 * show_user_select
 * This one is for users! shows a select/option statement so you can pick a user
 * to blame
 */
function show_user_select($name,$selected='',$style='') { 

	echo "<select name=\"$name\" style=\"$style\">\n";
	echo "\t<option value=\"\">" . _('None') . "</option>\n";

	$sql = "SELECT username as id,fullname FROM user ORDER BY fullname";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_assoc($db_results)) { 
		$select_txt = '';
		if ($r['id'] == $selected) { 
			$select_txt = 'selected="selected"';
		}

		echo "\t<option value=\"" . $r['id'] . "\" $select_txt>" . scrub_out($r['fullname']) . "</option>\n";
		
	} // end while users

} // show_user_select

/**
 * show_box_top
 * This function requires the top part of the box
 * it takes title as an optional argument
 */
function show_box_top($title='') { 

	require (conf('prefix') . '/templates/show_box_top.inc.php');	

} // show_box_top

/**
 * show_box_bottom
 * This function requires the bottom part of the box
 * it does not take any arguments
 */
function show_box_bottom() { 

	require (conf('prefix') . '/templates/show_box_bottom.inc.php');

} // show_box_bottom

/**
 * get_user_icon
 * this function takes a name and a returns either a text representation
 * or an <img /> tag 
 */
function get_user_icon($name) { 

	$icon_name = 'icon_' . $name . '.gif';

	if (file_exists(conf('prefix') . '/themes/' . $GLOBALS['theme']['path'] . '/images/' . $icon_name)) { 
		$img_url = conf('web_path') . conf('theme_path') . '/images/' . $icon_name;
	}
	else { 
		$img_url = conf('web_path') . '/images/' . $icon_name; 
	}

	$string = "<img src=\"$img_url\" border=\"0\" alt=\"$name\" title=\"$name\" />";

	return $string;

} // show_icon

/**
 * xml_from_array
 * This takes a one dimensional array and 
 * creates a XML document form it for use 
 * primarly by the ajax mojo
 */
function xml_from_array($array) { 

	$string = "<root>\n";
	foreach ($array as $key=>$value) { 
		/* We need to escape the value */
		$string .= "\t<$key><![CDATA[$value]]></$key>\n";
	}
	$string .= "</root>\n";

	return $string;

} // xml_from_array

/**
 * show_ajax_js
 * This displays the javascript array definition needed
 * For ajax to know what it should be replacing
 */
function show_ajax_js($name,$array) { 

	$elements = count($array);

	echo "<script type=\"text/javascript\" language=\"javascript\">\n";
	echo "<!--\n";
	echo "var $name = new Array($elements);\n";
	foreach ($array as $key=>$value) { 
		echo $name . "[$key] = \"$value\";\n";
	}
	echo "-->\n</script>\n";

} // show_ajax_js

?>
