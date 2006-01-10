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
	@header

 A template file

*/
$web_path = conf('web_path');
// let's put a couple of things in this file

if ($type == 'show_flagged_form') {
	$song = new Song($song_id);
	$song->format_song();
	if (!preg_match('/\.mp3$/', $song->file)) {
		echo "<p>Ampache can only edit MP3 file tags currently.<br/>";
		echo "<a href=\"".$_SERVER['HTTP_REFERER']."\">Back</a>";
		return;
	} // end if (!preg_match('/\.mp3$/',$song->file))
?>
	
	<p style="font-size: 10pt; font-weight: bold;"><?php echo _("Flag song"); ?></p>
	<p><?php echo _("Flag the following song as having one of the problems listed below.  Site admins will then take the appropriate action for the flagged files."); ?></p>
	
	<?php  if ($flag_text) { ?>
	<p style="color: red;"><?php echo  $flag_text ; ?></p>
	<?php  } ?>
	
	<form name="flag_song" method="post" action="<?php echo $web_path; ?>/flag.php">
		<table class="tabledata" cellpadding="3" cellspacing="1">
		<tr class="even">
			<td><?php echo _("File"); ?>:</td>
			<td><?php echo  $song->file; ?></td>
		</tr>
		<tr class="even">
			<td><?php echo _("Song"); ?>:</td>
			<td><b><?php echo  $song->f_title; ?></b> by <?php echo  $song->f_artist_full; ?></td>
		</tr>
		<tr class="even">
			<td><?php echo _("Reason to flag"); ?>:</td>
			<td><?php  show_flagged_popup($reason); ?></td>
		</tr>
		<tr class="even">
			<td><?php echo _("Comment"); ?>:</td>
			<td><input name="comment" type="text" size="50" value="<?php echo  $comment; ?>"></input>
			</td>
		</tr>
		<tr class="odd">
			<td> &nbsp; </td>
			<td>
				<input type="submit" value="<?php echo _("Flag Song"); ?>" />
				<input type="hidden" name="action" value="flag_song" />
			</td>
		</tr>
		</table>
		<input type="hidden" name="song" value="<?php echo  $song->id; ?>" />
	</form>

<?php 
} // end if ($type == 'show_flagged_form')
elseif ($type == 'show_flagged_songs') {
	$flags = get_flagged();
?>
	<p style="font-size: 10pt; font-weight: bold;">View Flagged Songs</p>
	<p>This is the list of songs that have been flagged by your Ampache users.  Use
	this list to determine what songs you need to re-rip or tags you need to update.</p>
	<?php
	if ($flags) { ?>
		<form name="flag_update" action="<?php echo $web_path; ?>/flag.php" method="post">
		<table class="tabledata" cellspacing="0" cellpadding="0" border="1">
		<tr class="table-header">
			<td>&nbsp;</td>
			<td>Song</td>
			<td>Flag</td>
			<td>New Flag:</td>
			<td>Flagged by</td>
			<td>ID3 Update:</td>
		</tr>
		<?php 
		foreach ($flags as $flag) {
			$song = new Song($flag->song);
			$song->format_song();
			$alt_title = $song->title;
			$artist = $song->f_artist;
			$alt_artist = $song->f_full_artist;
	
			echo "<tr class=\"even\">".
				"<td><input type=\"checkbox\" id=\"flag_".$flag->id."\" name=\"flag[]\" value=\"".$flag->id."\"></input></td>".
				"<td><a href=\"".$web_path."/song.php?song=$flag->song\" title=\"$alt_title\">$song->f_title</a> by ".
				"<a href=\"".$web_path."/artist.php?action=show&amp;artist=$song->artist_id\" title=\"$alt_artist\">$artist</a></td>".
				"<td>$flag->type</td><td>";
			$onchange = "onchange=\"document.getElementById('flag_".$flag->id."').checked='checked';\"";
			show_flagged_popup($flag->type, 'type', $flag->id."_newflag", $onchange);
			echo "</td><td>".$flag->username."<br />".date('m/d/y',$flag->date)."</td>";
			/*echo "<td><a href=\"catalog.php?action=fixed&flag=$flag->id\">Fixed</a></td></tr>\n";*/
			if ($flag->type === 'newid3') {
				echo "<td><input type=\"radio\" name=\"accept_".$flag->id."\" value=\"accept\" />Accept";
				echo "<input type=\"radio\" name=\"accept_".$flag->id."\" value=\"reject\" />Reject</td></tr>";
			} else {
				echo "<td><a href=\"".$web_path."/admin/song.php?action=edit&amp;song=".$flag->song."\">edit/view</a></td>";
				echo "</tr>\n";
			}  // end if ($flag->type === 'newid3') and else
		} // end foreach ($flags as $flag)
		?>
		<tr class="even"><td colspan="6"><input type="submit" name="action" value="Update Flags"></input></td></tr> 
		</table>
		</form>
<?php 	} else { ?>
	<p> You don't have any flagged songs. </p>
<?php	} // end if ($flags) and else
} // end elseif ($type == 'show_flagged_songs')
?>
