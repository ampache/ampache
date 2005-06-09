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
/*!
 @header Show Now Playing

*/

?>
<?php if (count($results)) { ?>
<table class="border" cellspacing="1" cellpadding="3" border="0" width="100%">
  <tr class="table-header">
    <td colspan="4"><?php echo _("Now Playing"); ?></td>
  </tr>
<?php
	foreach($results as $item) { 
		$song = $item['song'];
		$np_user = $item['user'];
		if (is_object($song)) {
                        $artist = $song->f_artist;
                        $album  = $song->get_album_name();
                        $text = "$song->f_title";
			if (!$np_user->fullname) { $np_user->fullname = "Unknown User"; }
                        echo "<tr class=\"even\">\n";
                        if (conf('use_auth')) { 
				echo "\t<td valign=\"center\">$np_user->fullname</td>\n"; 
			}
                        echo "\t<td><a title=\"$song->title\" href=\"$web_path/song.php?action=m3u&song=$song->id\">$text</a></td>\n";
			echo "\t<td><a title=\"$song->f_artist\" href=\"$web_path/artists.php?action=show&artist=$song->artist\">$song->f_artist</a> / ";
                        echo "\t<a title=\"$album\" href=\"$web_path/albums.php?action=show&album=$song->album\">$song->f_album</a></td>";
                        if (conf('play_album_art')) {
                                echo "\t<td align=\"center\">";
                                echo "<a target=\"_blank\" href=\"" . conf('web_path') . "/albumart.php?id=" . $song->album . "\">";
                                echo "<img align=\"center\" border=\"0\" src=\"" . conf('web_path') . "/albumart.php?id=" . $song->album . "&amp;fast=1\" alt=\"Album Art\" height=\"75\">";
                                echo "</a>\n";
                                echo "\t</td>\n";
                                echo "</tr>\n";
                        } // if album art on now playing
			else {
				echo "\n</tr>";
			}
                } // if it's a song
        } // while we're getting songs
?>
  </tr>
</table>
<? } ?>
