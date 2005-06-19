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
	@header Show mpd controls, this doesn't
		include the playlist, status and what have you.
		this looks a goodbit like local_play
*/
$web_path = conf('web_path'); 
?>
<div align="center">
<table border="0" cellpadding="3" cellspacing="0">
<tr class="table-header">
	<td colspan="2"><?php echo _("MPD Play Control"); ?></td>
</tr>
<tr class="even"> <td>
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
		<td>
<?php
	${$myMpd->state} = "style=\"background-color: black;color:white;\"";
?>
	<form action="<?php echo $web_path; ?>/amp-mpd.php" method="post" name="playcontrol" style="display:inline;">
		<input class="button" type="submit" title="<?php echo _("Prev"); ?>" name="action" value="|&lt; " />
		<input class="button" type="submit" title="<?php echo _("Stop"); ?>" name="action" value=" X " <?php echo $stop; ?> />
		<input class="button" type="submit" title="<?php echo _("Play"); ?>" name="action" value=" &gt; " <?php echo $play; ?> />
		<input class="button" type="submit" title="<?php echo _("Pause"); ?>" name="action" value=" = " <?php echo $pause; ?> />
		<input class="button" type="submit" title="<?php echo _("Next"); ?>" name="action" value= " &gt;|" />
	</form>
	</td>
</tr>
<tr>
	<td>
	<?php echo _("Loop"); ?>: 
	<form action="<?php echo $web_path; ?>/amp-mpd.php?action=loop" method="post" name="repeatcontrol" style="display:inline;">
	<?php 
		$repeat_name = "repeat_" . intval($myMpd->repeat);
		${$repeat_name} = "style=\"background-color: black;color:white;\"";
	?>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input class="button" type="submit" title="<?php echo _("On"); ?>" name="val" value="On" <?php echo $repeat_1; ?> />
		<input class="button" type="submit" title="<?php echo _("Off"); ?>" name="val" value="Off" <?php echo $repeat_0; ?> />
	</form>
	</td>
</tr>
<tr>
	<td>
	<?php echo _("Random"); ?>: 
	<form action="<?php echo $web_path; ?>/amp-mpd.php?action=random" method="post" name="randomcontrol" style="display:inline;">
	<?php
		$random_name = "random_" . intval($myMpd->random);
		${$random_name} = "style=\"background-color: black;color:white;\"";
	?>
		<input class="button" type="submit" title="<?php echo _("On"); ?>" name="val" value="On" <?php echo $random_1; ?> />
		<input class="button" type="submit" title="<?php echo _("Off"); ?>" name="val" value="Off" <?php echo $random_0; ?> />
	</form>
	</td>
</tr>
<tr>
	<td class="content">
                &nbsp;
        </td>
</tr>
</table>
</td>
	</tr>
<?php   if ( $myMpd->state == MPD_STATE_PLAYING or $myMpd->state == MPD_STATE_PAUSED ) {  ?>
  <tr><td>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
      <tr>
      	<td><?php echo _("Now Playing"); ?>: (<?php echo (round(($myMpd->current_track_position/$myMpd->current_track_length),2)*100) ?>% cmpl.)
	</td>
      </tr>
      <tr>
      	<td align="center"><?php echo $myMpd->playlist[$myMpd->current_track_id]['Artist']." - ".$myMpd->playlist[$myMpd->current_track_id]['Title'] ?></td>
      </tr>
    </table>
  </td></tr>
<?php  } ?>
<tr>
   <td>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
      <tr>
      	<td align="center">[ <a title="<?php echo _("Refresh the Playlist Window"); ?>" href="<?php echo conf('web_path'); if ($GLOBALS['user']->prefs['play_type'] == 'mpd' && conf('localplay_menu')) {echo "/mpd.php";} ?>"><?php echo _("refresh now"); ?></a> ]</td>
      </tr>
    </table>
  </td>
</tr>
</table>
<br />
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr><td align="center"><b><?php echo _("Server Playlist"); ?></b></td></tr>
  <tr><td>
<?php 
                $pl = $myMpd->GetPlaylist();
                if ( is_null($pl) ) echo "ERROR: " .$myMpd->errStr."\n";
                else {
			$maxlen = strlen(count($pl));
                        foreach ($pl as $id => $entry) {
                                $tblClass = ( $id == $myMpd->current_track_id ? "SongPlaying" : "Song" );
				$track = $id+1;
				
				// Make all number lengths equal
				$len = strlen($track);
		
				while ($len < $maxlen) {
        	                        $track = "0" . $track;
					$len++;
				}
				
 				$song_name = truncate_with_ellipse($entry['Artist'],conf('ellipse_threshold_artist')-3)  . " - " . truncate_with_ellipse($entry['Title'],conf('ellipse_threshold_title')-3);
			
                                echo "\t";
                                echo " <a title=\"Click to remove ".htmlspecialchars($entry['Title'])."\" href=\"".conf('web_path')."/amp-mpd.php?action=rem&amp;id=".$id."\">[" . $track . "]</a>"; 
                                echo " <a title=\"Click to jump to ".htmlspecialchars($entry['Title'])."\" href=\"".conf('web_path')."/amp-mpd.php?action=skipto&amp;val=".$id."\">$song_name</a>"; 
				echo "<br />\n";
                        }
                }
                if ( $myMpd->playlist_count == 0 ) {
                        echo "<i>(Playlist is empty)</i><br />\n";
                }
?>
  </td></tr>
<?php       if ( $myMpd->playlist_count > 0 ) { ?>
<tr>
	<td align="center" height="20">
  		[<a title="<?php echo _("Click to shuffle (randomize) the playlist"); ?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=shuffle"><?php echo _("shuffle"); ?></a>] 
		[<a title="<?php echo _("Click the clear the playlist"); ?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=clear"><?php echo _("clear"); ?></a>]
	</td>
</tr>
<?php   }  ?>
</table>
</div>
