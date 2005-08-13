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

if (!isset($_REQUEST['match'])) { $_REQUEST['match'] = "Browse"; }
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = "match"; }
$action = scrub_in($_REQUEST['action']);

show_template('header');
show_menu_items('Artists'); 
show_clear();


switch($action) {
    case 'show':
    case 'Show':
	show_alphabet_list('artists','artists.php');
	$artist = new Artist(scrub_in($_REQUEST['artist']));
	$artist->show_albums();
	break;

    case 'show_all_songs':
        $artist = get_artist_name(scrub_in($_REQUEST['artist']));
        echo "<h2>" . _("All songs by") . " $artist</h2>";
        $song_ids = get_song_ids_from_artist($_REQUEST['artist']);
        show_songs($song_ids);
        break;

    case 'update_from_tags':

        $artist = new Artist($_REQUEST['artist']);

        echo "<br /><b>" . _("Starting Update from Tags") . ". . .</b><br />\n";

        $catalog = new Catalog();
        $catalog->update_single_item('artist',$_REQUEST['artist']);

        echo "<br /><b>" . _("Update From Tags Complete") . "</b> &nbsp;&nbsp;";
        echo "<a href=\"" . conf('web_path') . "/artists.php?action=show&amp;artist=" . $_REQUEST['artist'] . "\">[" . _("Return") . "]</a>";

	break;
    case 'match':
    case 'Match':
	$match = scrub_in($_REQUEST['match']);
	preg_match("/^(\w*)/", $match, $matches);
	show_alphabet_list('artists','artists.php',$match);
	if ($match === "Browse") {
		show_alphabet_form('',_("Show Artists starting with"),"albums.php?action=match");
		show_artists();
	}
	elseif ($match === "Show_all") {
		show_alphabet_form('',_("Show Artists starting with"),"albums.php?action=match");
		$_SESSION['view_offset_limit'] = 999999;
		show_artists();
	}		
        else {
		$chr = preg_replace("/[^a-zA-Z0-9]/", "", $matches[1]);
		show_alphabet_form($chr,_("Show Artists starting with"),"albums.php?action=match");

		if ($chr == '') {
			show_artists('A');
		}
		else {
			show_artists($chr);
		}
	}
	break;

    default:
	show_alphabet_list('artists','artists.php');
	show_alphabet_form('',_("Show Artists starting with"),"albums.php?action=match");
	show_artists('A');
	break;

}
echo "<br /><br />";
show_page_footer ('Artists', '',$user->prefs['display_menu']);
?>
