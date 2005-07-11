<?php
/*

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

/*

 Do most of the dirty work of displaying the mp3 catalog

*/

require_once("modules/init.php");

// We'll set any input parameters here
if(!isset($_REQUEST['match'])) { $_REQUEST['match'] = "Browse"; }
if(isset($_REQUEST['match'])) $match = scrub_in($_REQUEST['match']);
if(isset($_REQUEST['album'])) $album = scrub_in($_REQUEST['album']);
if(isset($_REQUEST['artist'])) $artist = scrub_in($_REQUEST['artist']);
$_REQUEST['artist_id'] = scrub_in($_REQUEST['artist_id']);

show_template('header');
show_menu_items('Albums');
show_clear();

if ($_REQUEST['action'] === 'clear_art') { 
	if (!$user->has_access('25')) { access_denied(); } 
	$album = new Album($_REQUEST['album_id']);
	$album->clear_art();
	show_confirmation(_("Album Art Cleared"),_("Album Art information has been removed form the database"),"/albums.php?action=show&album=" . $album->id);

} // clear_art
// if we have album
elseif (isset($album)) { 
	$album = new Album($album);
	$album->format_album();

	$artist_obj = new Artist($artist_obj);

	require (conf('prefix') . "/templates/show_album.inc");
	
	if (isset($artist) && $artist_obj->name == "Unknown (Orphaned)" ) {
		$song_ids = get_song_ids_from_artist_and_album($artist, $album->id);
	}
	else {
		$song_ids = get_song_ids_from_album($album->id);
	}
	show_songs($song_ids,0,$album);
} // isset(album)

// Finds the Album art from amazon
elseif ($_REQUEST['action'] === 'find_art') {

	if (!$user->has_access('25')) { access_denied(); }

	/* Echo notice if no amazon token is found, but it's enabled */
	if (in_array('amazon',conf('album_art_order')) AND !conf('amazon_developer_key')) { 
		echo "<br /><div class=\"fatalerror\">Error: No Amazon Developer Key set, amazon album art searching will not work</div>";
	}

        $album = new Album($_REQUEST['album_id']);
	$result = $album->find_art($_REQUEST['cover']);
	if ($result) {
		show_confirmation(_("Album Art Located"),_("Album Art information has been located in Amazon. If incorrect, click \"Reset Album Art\" below to remove the artwork."),"/albums.php?action=show&album=" . $album->id);
		echo "&nbsp;[ <a href=\"" . conf('web_path') . "/albums.php?action=clear_art&album_id=" . $album->id . "\">Reset Album Art</a> ]";
		echo "<p align=left><img src=\"" . conf('web_path') . "/albumart.php?id=" . $album->id . "\"></p>";
		echo "<p><form name=\"cover\" method=\"get\" action=\"".$_SERVER['PHP_SELF']."\">";
		echo "Enter URL to album art ";
		echo "<input type=\"text\" size=\"40\" id=\"cover\" name=\"cover\" value=\"\" />\n";
		echo "<input type=\"hidden\" name=\"action\" value=\"find_art\" />\n";
		echo "<input type=\"hidden\" name=\"album_id\" value=\"$album->id\" />\n";
		echo "<input type=\"submit\" value=\"" . _("Get Art") . "\" />\n";
		echo "</form>"; 
	}
        else {
                show_confirmation(_("Album Art Not Located"),_("Album Art could not be located at this time. This may be due to Amazon being busy, or the album not being present in their collection."),"/albums.php?action=show&album=" . $album->id);
                echo "<p><form name=\"cover\" method=\"get\" action=\"".$_SERVER['PHP_SELF']."\">";
                echo "Enter URL to album art ";
                echo "<input type=\"text\" size=\"40\" id=\"cover\" name=\"cover\" value=\"\" />";
                echo "<input type=\"hidden\" name=\"action\" value=\"find_art\" />";
                echo "<input type=\"hidden\" name=\"album_id\" value=\"$album->id\" />&nbsp;&nbsp;&nbsp;";
		echo "<input type=\"submit\" value=\"" . _("Get Art") . "\" />\n";
                echo "</form>";
	}
} // find_art 

// Updates Album from tags
elseif ($_REQUEST['action'] === 'update_from_tags') {

	$album = new Album($_REQUEST['album_id']);

	echo "<br /><b>" . _("Starting Update from Tags") . ". . .</b><br />\n";

	$catalog = new Catalog();
	$catalog->update_single_item('album',$_REQUEST['album_id']);

	echo "<br /><b>" . _("Update From Tags Complete") . "</b> &nbsp;&nbsp;";
	echo "<a href=\"" . conf('web_path') . "/albums.php?action=show&amp;album=" . $_REQUEST['album_id'] . "\">[" . _("Return") . "]</a>";

} // update_from_tags

else {

	if (strlen($_REQUEST['match']) < '1') { $match = 'none'; }

	// Setup the View Ojbect
        $view = new View();
        $view->import_session_view();

	switch($match) {
		case 'Show_all':
			show_alphabet_list('albums','albums.php','show_all');
			echo "<form name=\"f\" method=\"get\" action=\"".$_SERVER['PHP_SELF']."\"><label for=\"match\" accesskey=\"S\">" . _("<u>S</u>how all albums") ."</label> <input type=\"text\" size=\"3\" id=\"match\" name=\"match\" value=\"\"></input><input type=\"hidden\" name=\"action\" value=\"match\"></input></form>\n";
			$offset_limit = 99999;
			$sql = "SELECT id FROM album";
			break;
                case 'Show_missing_art':
                        show_alphabet_list('albums','albums.php','show_missing_art');
                        echo "<form name=\"f\" method=\"get\" action=\"".$_SERVER['PHP_SELF']."\"><label for=\"match\" accesskey=\"S\">" . _("<u>S</u>how all albums") ."</label> <input type=\"text\" size=\"3\" id=\"match\" name=\"match\" value=\"\"></input><input type=\"hidden\" name=\"action\" value=\"match\"></input></form>\n";
                        $offset_limit = 99999;
                        $sql = "SELECT id FROM album where art is null";
                        break; 
		case 'Browse':
		case 'show_albums':
			show_alphabet_list('albums','albums.php','browse');
			echo "<form name=\"f\" method=\"get\" action=\"".$_SERVER['PHP_SELF']."\"><label for=\"match\" accesskey=\"S\">" . _("<u>S</u>how only albums starting with") . "</label> <input type=\"text\" size=\"3\" id=\"match\" name=\"match\" value=\"\"></input><input type=\"hidden\" name=\"action\" value=\"match\"></input></form>\n";
			$sql = "SELECT id FROM album";
			break;
		case 'none':
			show_alphabet_list('albums','albums.php','a');
			echo "<p style=\"font: 10pt bold;\">".
				_("Select a starting letter or Show all") . "</p>";
			echo "<form name=\"f\" method=\"get\" action=\"".$_SERVER['PHP_SELF']."\"><label for=\"match\" accesskey=\"S\">" . _("<u>S</u>how only albums starting with") . "</label> <input type=\"text\" size=\"3\" id=\"match\" name=\"match\" value=\"\"></input><input type=\"hidden\" name=\"action\" value=\"match\"></input></form>\n";
			$sql = "SELECT id FROM album WHERE name LIKE 'a%'";
			break;
		default:
			show_alphabet_list('albums','albums.php',$match);
			echo "<form name=\"f\" method=\"get\" action=\"".$_SERVER['PHP_SELF']."\"><label for=\"match\" accesskey=\"S\">" . _("<u>S</u>how only albums starting with") . "<input type=\"text\" size=\"3\" id=\"match\" name=\"match\" value=\"$match\"></input><input type=\"hidden\" name=\"action\" value=\"match\"></input></p></form>\n";
			echo "<br /><br />";
			$sql = "SELECT id FROM album WHERE name LIKE '$match%'";
	} // end switch

	// if we are returning
	if ($_REQUEST['keep_view']) { 
                $view->initialize();
	}

	// If we aren't keeping the view then initlize it
	elseif ($sql) {
		$db_results = mysql_query($sql, dbh());
		$total_items = mysql_num_rows($db_results);
		if ($match != "Show_all") { $offset_limit = $_SESSION['userdata']['offset_limit']; }
		$view = new View($sql, 'albums.php','name',$total_items,$offset_limit);	
	} 

	else { $view = false; }

	if ($view->base_sql) { 
		$albums = get_albums($view->sql);
		show_albums($albums,$view);	
	}

} // else no album

echo "<br /><br />";
show_page_footer ('Albums', '',$user->prefs['display_menu']);
?>
