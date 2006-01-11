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
function fmt_time($seconds) {return sprintf ("%d:%02d", $seconds/60, $seconds % 60);}
global $condPL;
?>

<div align="center">
<table border="0" cellpadding="3" cellspacing="0" class="tabledata"><!-- MPD Control table -->
<tr class="table-header">
	<td colspan="2"><?php echo _("MPD Play Control"); ?></td>
</tr>
<tr class="even">
<td>
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			<?php ${$myMpd->state} = "'selected_button'";
			if (true)  /* rigged to do AJAX for now; change to conf('AJAX') later*/ { ?>
				<!--   <input type="button" class="unselected_button" value="times" onclick="timestuff();"/> -->
				<input type="button" class="unselected_button" value="|&lt; " onclick="startRequest('action=Prev');"/>
				<input type="button" class=<?php echo ($stop ? "'selected_button'" : "'unselected_button'") ?> id="stop_button" value=" X " onclick="startRequest('action=stop');"/>
				<input type="button" class=<?php echo ($play ? "'selected_button'" : "'unselected_button'") ?> id="play_button" value=" &gt; " onclick="startRequest('action=play');"/>
				<input type="button" class=<?php echo ($pause ? "'selected_button'" : "'unselected_button'") ?> id="pause_button" value=" | | "  onclick="startRequest('action=pause');"/>
				<input type="button" class="unselected_button" value=" &gt;|" onclick="startRequest('action=Next');"/>
				<?php
			}
			else  { ?>
				<form action="<?php echo $web_path; ?>/amp-mpd.php" method="post" name="playcontrol" style="display:inline; white-space: nowrap">
					<!-- these used to have class="button" -->
					<input type="submit" title="<?php echo _("Prev"); ?>" name="action" value="|&lt; " />
					<input type="submit" title="<?php echo _("Stop"); ?>" name="action" value=" X " <?php echo $stop; ?> />
					<input type="submit" title="<?php echo _("Play"); ?>" name="action" value=" &gt; " <?php echo $play; ?> />
					<input type="submit" title="<?php echo _("Pause"); ?>" name="action" value=" | | " <?php echo $pause; ?> />
					<input type="submit" title="<?php echo _("Next"); ?>" name="action" value= " &gt;|" />
				</form>
			<?php } ?>
	</td>
	</tr>
	<tr>
		<td class="content"> Volume: <b><span id='volume'><?php echo $myMpd->volume ?></span>%</b></td>
	</tr>
	<tr>
		<td class="content" align="center"> 
			<?php
			if (true)  /* rigged to do AJAX for now; change to conf('AJAX') later */ { ?> 
			<input type="button" value="0"   onclick="startRequest('action=setvol&amp;param1=0');"/>
			<input type="button" value="-25" onclick="startRequest('action=adjvol&amp;param1=-25');"/>
			<input type="button" value="-10" onclick="startRequest('action=adjvol&amp;param1=-10');"/>
			<input type="button" value="+10" onclick="startRequest('action=adjvol&amp;param1=10');"/>
			<input type="button" value="+25" onclick="startRequest('action=adjvol&amp;param1=25');"/>
			<?php
			}
			else  { ?>
				[<a href="<?php echo $web_path; ?>/amp-mpd.php?action=setvol&amp;val=0">mute</a>
				<a href="<?php echo $web_path; ?>/amp-mpd.php?action=adjvol&amp;val=-25">-25</a>
				<a href="<?php echo $web_path; ?>/amp-mpd.php?action=adjvol&amp;val=-10">-10</a>
				<a href="<?php echo $web_path; ?>/amp-mpd.php?action=adjvol&amp;val=+10">+10</a>
				<a href="<?php echo $web_path; ?>/amp-mpd.php?action=adjvol&amp;val=+25">+25</a>] ';
			<?php } ?>
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
				${$random_name} = "style=\"background-color: black;color:white;\""; ?>
				<input class="button" type="submit" title="<?php echo _("On"); ?>" name="val" value="On" <?php echo $random_1; ?> />
				<input class="button" type="submit" title="<?php echo _("Off"); ?>" name="val" value="Off" <?php echo $random_0; ?> />
			</form>
		</td>
	</tr>
	<tr>
		<td class="content">&nbsp;</td>
	</tr>
	</table>
</td>
</tr>
<tr>
<td>
	<table id="mpd_np" <?php   if (($myMpd->state == "stop") or ($myMpd->state == "pause")) { echo 'class="nodisplay"'; } ?> border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td class="npsong"><b><?php echo _("Now Playing :")?></b></td>
	</tr>
	<tr>
		<td align="center" class="npsong"><?php
			if (!$myMpd->playlist[($myMpd->current_track_id)]['Title']) {
				list($tmp, $id, $tmp) = preg_split("/(song=|&)/", $myMpd->playlist[($myMpd->current_track_id)]['file']);
				$r = new Song($id);
				$myMpd->playlist[($myMpd->current_track_id)]['Title'] = $r->title;
				$myMpd->playlist[($myMpd->current_track_id)]['Artist'] = $r->get_artist_name();
				$myMpd->playlist[($myMpd->current_track_id)]['Album'] = $r->get_album_name();
			}
			echo "<span id='mpd_npinfo'>";
			echo ($myMpd->current_track_id+1).
			". ".$myMpd->playlist[$myMpd->current_track_id]['Artist'].
			" - ".$myMpd->playlist[$myMpd->current_track_id]['Title'].
			" - ".$myMpd->playlist[$myMpd->current_track_id]['Album'].
			" - ".fmt_time($myMpd->playlist[$myMpd->current_track_id]['Time']);
			echo "</span>";	?>
		</td>
	</tr>
	<tr> 
		<td align="center" class="npsong">
			<span id="mpd_cur_track_pos"><?php echo fmt_time($myMpd->current_track_position)?></span>
			(<span id="mpd_pctplayed"><?php echo (round(($myMpd->current_track_position/$myMpd->current_track_length),2)*100)."</span>%) played" ?>
		</td>
	</tr>
	<tr>
		<td>
			<b><?php echo _("On Deck ")?><?php echo _("(in ").
			"<span id = 'mpd_on_deck_in'>".fmt_time($myMpd->current_track_length - $myMpd->current_track_position)."</span>)"?>
			</b>
		</td>
	</tr>
	<tr>
		<td align="center"><?php
			if (!$myMpd->playlist[($myMpd->current_track_id+1)]['Title']) {
				list($tmp, $id, $tmp) = preg_split("/(song=|&)/", $myMpd->playlist[($myMpd->current_track_id+1)]['file']);
				$r = new Song($id);
				$myMpd->playlist[($myMpd->current_track_id+1)]['Title'] = $r->title;
				$myMpd->playlist[($myMpd->current_track_id+1)]['Artist'] = $r->get_artist_name();
				$myMpd->playlist[($myMpd->current_track_id+1)]['Album'] = $r->get_album_name();
			}
			echo ($myMpd->current_track_id+2).
			". ".$myMpd->playlist[($myMpd->current_track_id+1)]['Artist'].
			" - ".$myMpd->playlist[($myMpd->current_track_id+1)]['Title'].
			" - ".$myMpd->playlist[($myMpd->current_track_id+1)]['Album'].
			" - ".fmt_time($myMpd->playlist[($myMpd->current_track_id+1)]['Time']);?>
		</td>
	</tr>
	</table>
</td>
</tr>
</table>
<?php
/***** moving this into javascript_refresh.js

echo '<script language="JavaScript" type="text/javascript"> var mpd_elapsed = '. $myMpd->current_track_position.
     ';  var mpd_song_length = '. $myMpd->current_track_length .
     ';  var mpd_songid = '.$myMpd->current_track_id. 
     ';  var mpd_state = "'. $myMpd->state .'"; </script>';

****** end move
******/

?>
</div>
