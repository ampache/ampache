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
?>
<?php if (count($results)) { ?>
<table class="border" cellspacing="1" cellpadding="3" border="0" width="100%">
<tr class="table-header">
	<td colspan="4"><?php echo _('Now Playing'); ?></td>
</tr>
<?php
$user = $GLOBALS['user'];
foreach($results as $item) { 
	$song = $item['song'];
	$np_user = $item['user'];
	if (is_object($song)) {
		$artist = $song->f_artist;
		$album  = $song->get_album_name();
		$text = "$song->f_title";
		if (!$np_user->fullname) { $np_user->fullname = "Unknown User"; }
		if ($GLOBALS['user']->username == $np_user->username) {
			$sql = "SELECT start_time FROM now_playing WHERE user ='".$user->username."'";
			$db_results = mysql_query($sql, dbh());
			echo "<tr class=\"even\">\n"; 
		} else {
			echo "<tr class=\"even\">\n";
		}
		if (conf('use_auth')) {
			 echo "\t<td valign=\"middle\">$np_user->fullname</td>\n";
		}
		echo "\t<td><a title=\"" . scrub_out($song->title) . "\" href=\"$web_path/song.php?action=m3u&amp;song=$song->id\">$text</a></td>\n";
		echo "\t<td><a title=\"" . scrub_out($song->f_artist) . "\" href=\"$web_path/artists.php?action=show&amp;artist=$song->artist\">$song->f_artist</a> / ";
		echo "\t<a title=\"" . scrub_out($album) . "\" href=\"$web_path/albums.php?action=show&amp;album=$song->album\">$song->f_album</a></td>";
		if (conf('play_album_art')) {
			echo "\t<td align=\"center\">";
			$aa_url = $web_path . "/albumart.php?id=$song->album&amp;type=popup";
			echo "<a target=\"_blank\" href=\"$aa_url\" onclick=\"popup_art('$aa_url'); return false;\">";
			echo "<img align=\"middle\" border=\"0\" src=\"$web_path/albumart.php?id=$song->album&amp;fast=1&amp;thumb=1\" alt=\"Album Art\" height=\"75\" />";
			echo "</a>\n";
			echo "\t</td>\n";
			echo "</tr>\n";
		} // if album art on now playing
		else { echo "\n</tr>"; }
	} // if it's a song
} // while we're getting songs
?>
</table>
<?php } ?>
