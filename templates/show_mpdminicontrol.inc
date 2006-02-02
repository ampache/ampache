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
require_once ("javascript_refresh.inc");
?>

<div align="center"><!--  Is this div neccesary??? or is the below table not needed???-->
<table border="0" cellpadding="3" cellspacing="0"><!-- MPD Control table -->
<tr>
<td>
	<table id="mpd_control" border="0" cellpadding="0" cellspacing="0" class="even" align="center">
<!--	<th class="table-header"><?php echo _("MPD Play Control"); ?></th> -->
	<tr>
		<td>
			<?php ${$myMpd->state} = "class='selected_button'";
			if (true)  /* rigged to do AJAX for now; change to conf('AJAX') later*/ { ?>
				<!--  for testing <input type="button" value="times" onclick="timestuff();"/> -->
				<input type="button" value="|&lt; " onclick="startRequest('action=Prev');"/>
				<input type="button" <?php echo $stop ?> id="stop_button" value=" X " onclick="startRequest('action=stop');"/>
				<input type="button" <?php echo $play ?> id="play_button" value=" &gt; " onclick="startRequest('action=play');"/>
				<input type="button" <?php echo $pause ?> id="pause_button" value=" | | "  onclick="startRequest('action=pause');"/>
				<input type="button" value=" &gt;|" onclick="startRequest('action=Next');"/>
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
		<td class="content">
		Played <b><span id="mpd_cur_track_pos"><?php echo format_time($myMpd->current_track_position)?></span></b>
                 (<span id="mpd_pctplayed"><?php echo (round(($myMpd->current_track_position/$myMpd->current_track_length),2)*100)."</span>%) of " .
                  format_time($myMpd->current_track_length); ?>
		  - Vol: <b><span id='volume'><?php echo $myMpd->volume ?></span>%</b> 

</td>
	</tr>
	<tr>
		<td class="content"> 
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
	</table>
</td>
</tr>
</table>
</div>
