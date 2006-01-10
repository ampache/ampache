<?php
/*

 Copyright (c) 2004 Ampache.org
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
?>

<p style="font-size: 10pt; font-weight: bold;">View Flagged Songs</p>
<p>This is the list of songs that have been flagged by you or other Ampache users.  Use
this list to determine what songs you need to re-rip or tags you need to update.</p>

<?php
if ($flags) { ?>
	<form name="songs" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<table class="tabledata" cellspacing="0" cellpadding="0" >
	<tr class="table-header">
		<td><a href="#" onclick="check_songs(); return false;">Select</a></td>
		<td><?php echo _("Song"); ?></td>
		<td><?php echo _("Flag"); ?></td>
		<td><?php echo _("New Flag"); ?>:</td>
		<td><?php echo _("Flagged by"); ?></td>
		<td><?php echo _("ID3 Update"); ?>:</td>
		<td><?php echo _("Comment"); ?>:</td>
	</tr>
	<?php 
	foreach ($flags as $flag) {	
		$song = new Song($flag['song']);
		$song->format_song();
		$alt_title = $song->title;
		$artist = $song->f_artist;
		$alt_artist = $song->artist;
	
		echo "<tr class=\"even\">" .
			"<td><input type=\"checkbox\" name=\"song[]\" id=\"flag_".$flag['song']."\" value=\"".$flag['song']."\"></input></td>" .
			"<td><a href=\"".$web_path."/song.php?song=".$flag['song']."\" title=\"$alt_title\">$song->f_title</a> by " .
			"<a href=\"".$web_path."/artist.php?action=show&amp;artist=$song->artist_id\" title=\"$alt_artist\">$artist</a></td>" .
			"<td>".$flag['type']."</td><td>";
		$onchange = "onchange=\"document.getElementById('flag_".$flag['song']."').checked='checked';\"";
		show_flagged_popup($flag['type'],'type',$flag['song']."_newflag", $onchange);
		echo "</td><td>".$flag['username']."<br />".date('m/d/y',$flag['date'])."</td>";
		if ($flag['type'] === 'newid3') {
			echo "<td><input type=\"radio\" name=\"".$flag['song']."_accept\" value=\"accept\" $onchange />" . _("Accept") . "<br />";
			echo "<input type=\"radio\" name=\"".$flag['song']."_accept\" value=\"reject\" $onchange />" . _("Reject");
			echo "</td>";
		}
		else {
			echo "<td><a href=\"".$web_path."/admin/song.php?action=edit&amp;song=".$flag['song']."\">edit/view</a></td>";
		} //end ($flag['type'] === 'newid3') and else
		echo "<td>" . $flag['comment'] . "</td>";
		echo "</tr>\n";
	} //end foreach ($flags as $flag)
	?>
	<tr class="even">
		<td colspan="7">
			<input type="submit" name="action" value="Update Flags"></input>
			<input type="submit" name="action" value="Edit Selected"></input>
			<input type="submit" name="action" value="Clear Edit List"></input>
		</td>
	</tr> 
	</table>
	</form>
<?php  } else { ?>
<p><?php echo _("You don't have any flagged songs"); ?></p>
<?php  } ?>


