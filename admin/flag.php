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
 * Flag Admin Document
 * This document handles the administrative aspects of 
 * flagging. 
 */

require('../modules/init.php');

if (!$GLOBALS['user']->has_access('100')) { 
	access_denied();
	exit();
}

show_template('header');

$action = scrub_in($_REQUEST['action']);

switch ($action) {
	case 'edit_song':
		$catalog = new Catalog();
		$song = new Song($_REQUEST['song_id']);
		$new_song = $song; 
	
		/* Setup the vars so we can use the update_song function */ 
		$new_song->title 	= scrub_in($_REQUEST['title']);
		$new_song->track 	= scrub_in($_REQUEST['track']);
		$new_song->year  	= scrub_in($_REQUEST['year']);
		$new_song->comment	= scrub_in($_REQUEST['comment']);
		$new_song->genre	= scrub_in($_REQUEST['genre']);
		$new_song->album	= scrub_in($_REQUEST['album']);
		$new_song->artist	= scrub_in($_REQUEST['artist']);
		/* Check the drop down vs string bs */
		if (strlen($_REQUEST['genre_string'])) { 
			$new_song->genre = $catalog->check_genre($_REQUEST['genre_string']);
		}
		if (strlen($_REQUEST['album_string'])) { 
			$new_song->album = $catalog->check_album($_REQUEST['album_string']);
		}
		if (strlen($_REQUEST['artist_string'])) { 
			$new_song->artist = $catalog->check_artist($_REQUEST['artist_string']);
		}
		/* Update this mofo */
		$song->update_song($song->id,$new_song);
		
		/* Add a tagging record of this so we can fix the file */
		if ($_REQUEST['flag']) { 
			$flag = new Flag();
			$flag->add($song->id,'song','retag','Edited Song, auto-tag');
		}
		show_confirmation(_('Song Updated'),_('The requested song has been updated'),$_SESSION['source']);
	break;
	case 'show_edit_song':
		$_SESSION['source'] = return_referer();
		$song = new Song($_REQUEST['song']);
		$song->format_song();
		require_once (conf('prefix') . '/templates/show_edit_song.inc.php');
        break;
	case 'disable':
		// If we pass just one, make it still work
	    	if (!is_array($_REQUEST['song_ids'])) { $song_obj->update_enabled(0,$_REQUEST['song_ids']); }
		else {
		    	foreach ($_REQUEST['song_ids'] as $song_id) {
				$song_obj->update_enabled(0,$song_id);
			} // end foreach
		} // end else
		show_confirmation(_('Songs Disabled'),_('The requested song(s) have been disabled'),return_referer());
	break;
	case "enabled":
		// If we pass just one, make it still work
	        if (!is_array($_REQUEST['song_ids'])) { $song_obj->update_enabled(1,$_REQUEST['song_ids']); }
		else {
		        foreach ($_REQUEST['song_ids'] as $song_id) {
				$song_obj->update_enabled(1,$song_id);
			} // end foreach
		} // end else
	        show_confirmation(_('Songs Enabled'),_('The requested song(s) have been enabled'),return_referer());
        break;
	default:
	break;
} // end switch


/*
	@function edit_song_info
	@discussion yea this is just wrong 
*/
function edit_song_info($song) {
    $info = new Song($song);
    preg_match("/^.*\/(.*?)$/",$info->file, $short);
    $filename = htmlspecialchars($short[1]);
    if(preg_match('/\.ogg$/',$short[1]))
    {
        $ogg = TRUE;
        $oggwarn = "<br/><br><em>This file is an OGG file, which Ampache only has limited support for.<br/>";
        $oggwarn .= "You can make changes to the database here, but Ampache will not change the actual file's information.</em><br/><br/>";
    }

echo <<<EDIT_SONG_1
<p><b>Editing $info->title</b></p>
<form name="update_song" method="post" action="song.php">
<table class="border" cellspacing="0">
  <tr class="table-header">
  	<td colspan="3"><b>Editing $info->title</b></td>
  </tr>

  <tr class="odd">
    <td>File:</td>
    <td colspan="2">$filename $oggwarn</td>
  </tr>

  <tr class="odd">
    <td>Title:</td>
    <td colspan="2"><input type="text" name="title" size="60" value="$info->title" /></td>
  </tr>

  <tr class="even">
    <td>Artist:</td>
    <td>
EDIT_SONG_1;
    show_artist_pulldown($info->artist);
echo <<<EDIT_SONG_2
    </td>
    <td>or <input type="text" name="new_artist" size="30" value="" /></td>
  </tr>

  <tr class="odd">
    <td>Album:</td>
    <td>
EDIT_SONG_2;
    show_album_pulldown($info->album);
echo <<<EDIT_SONG_3
    </td>
    <td>or <input type="text" name="new_album" size="30" value="" /></td>
  </tr>

  <tr class="even">
    <td>Track:</td>
    <td colspan="2"><input type="text" size="4" maxlength="4" name="track" value="$info->track"></input></td>
  </tr>

  <tr class="odd">
    <td>Genre:</td>
    <td colspan="2">
EDIT_SONG_3;
    show_genre_pulldown('genre',$info->genre);
echo <<<EDIT_SONG_4
  </td>
</tr>	
  <tr class="even">
    <td>Year</td>
    <td colspan="2"><input type="text" size="4" maxlength="4" name="year" value="$info->year"></input></td>
  </tr>

EDIT_SONG_4;
if(!$ogg)
{
echo <<<EDIT_SONG_5
  <tr class="even">
    <td>&nbsp;</td>
    <td><input type="checkbox" name="update_id3" value="yes"></input>&nbsp; Update id3 tags </td>
    <td>&nbsp;</td>
  </tr>
EDIT_SONG_5;
}
echo <<<EDIT_SONG_6
  <tr class="odd">
    <td> &nbsp; </td>
    <td colspan="2"> 
    	 <input type="hidden" name="song" value="$song" /> 
         <input type="hidden" name="current_artist_id" value="$info->artist" /> 
         <input type="submit" name="action" value="Update" /> 
    </td>
  </tr>
</table>

</form>
EDIT_SONG_6;
}

show_footer();
?>

