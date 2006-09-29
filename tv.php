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

/*!
	@header TV Display for Ampache
	@discussion BIG now playing and (soon) on deck
*/
require_once('lib/init.php');


if (conf('refresh_interval')) { 
	echo '<script language="JavaScript" type="text/javascript"> var mpd_elapsed = '.$myMpd->current_track_position." </script>\n";
	echo '<script language="JavaScript" type="text/javascript"> var mpd_song_length = '.$myMpd->current_track_length." </script>\n";
	echo '<script language="JavaScript" type="text/javascript"> var mpd_state = "'.$myMpd->state.'" </script>';
	}
?>

<?php
$dbh = dbh();
$web_path = conf('web_path');

/* get playlist */

if ($user->prefs['play_type'] == 'mpd') {
$mpddir = conf('mpd_dir')."/";
$sql = "SELECT song.id FROM song WHERE file = \"".
                $mpddir.$myMpd->playlist[$myMpd->current_track_id]['file']."\"";
        $db_results = @mysql_query($sql,dbh());
        while ($r = mysql_fetch_assoc($db_results)) {
                $song = new Song($r['id']);
                $song->format_song();
                $np_user = new User(0,$user->id);
                $results[] = array('song'=>$song,'user'=>$np_user);
        } // end while

}
else {
        $sql = "SELECT song_id,user_id FROM now_playing ORDER BY start_time DESC";
        $db_results = mysql_query($sql, dbh());
        while ($r = mysql_fetch_assoc($db_results)) {
                $song = new Song($r['song_id']);
                $song->format_song();
                $np_user = new User(0,$r['user_id']);
                $results[] = array('song'=>$song,'user'=>$np_user);
        } // end while
     } // end else

?>

<?php if (count($results)) { ?>
<!-- Big Daddy Table -->

<table style="border: thin solid #000000; margin: 25 0  0 25px" class = "body" cellspacing="1" cellpadding="3" border="0" width=900>
  <tr>
    <td  class="rowheader" style="background:#F6F600;font: italic 25 pt 'Times Roman', serif" colspan="2"><?php echo _("Now Playing"); ?></td>
  </tr>
<?php
foreach($results as $item) { 
	$song = $item['song'];
	$np_user = $item['user'];
	if (is_object($song)) {
        	echo '<tr><td>';
		echo "<table>\n";
/*		echo '<tr style="background:#F6F670"><td style="font: 35 pt Arial, sans-serif; height:125px; width:500">'.$song->f_title."</td></tr>\n";
		echo '<tr style="background:#F6F650"><td style="font: 35 pt Arial, sans-serif; height:125px">'.$song->f_artist."</td></tr>\n";
		echo '<tr style="background:#F6F670"><td style="font: 35 pt Arial, sans-serif; height:125px">'.$song->get_album_name()."</td></tr>\n";
*/
		echo '<tr class="npsong"><td style="font: 35 pt Arial, sans-serif; height:125px; width:500">'.$song->f_title."</td></tr>\n";
		echo '<tr class="npsong"><td style="font: 35 pt Arial, sans-serif; height:125px">'.$song->f_artist."</td></tr>\n";
		echo '<tr class="npsong"><td style="font: 35 pt Arial, sans-serif; height:125px">'.$song->get_album_name()."</td></tr>\n";
		echo "</table>\n";
                if (conf('play_album_art')) {
                	echo "\t<td align=\"center\">";
                        echo "<a target=\"_blank\" href=\"" . conf('web_path') . "/albumart.php?id=" . $song->album . "\">";
                        echo "<img align=\"middle\" border=\"0\" src=\"" . conf('web_path') . "/albumart.php?id=" . $song->album .
			"&amp;fast=1\" alt=\"Album Art\" height=\"350\" />";
                        echo "</a>\n";
                        echo "\t</td>\n";
                        echo "</tr>\n";
                	} // if album art on now playing
		else {
			echo "\n<td>\n</tr>";
		}
        } // if it's a song
} // while we're getting songs
?>
</table>
<?php } ?>

