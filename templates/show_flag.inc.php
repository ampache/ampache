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

$web_path = conf('web_path');
$type = scrub_in($_REQUEST['type']);

switch ($type) { 
	case 'song':
		$song = new Song($_REQUEST['id']);
		$song->format_song();
		$title	= scrub_out($song->f_title . " by " . $song->f_artist_full);
		$file 	= scrub_out($song->file);
	break;
	case 'album':
	break;
	case 'artist':
	break;
	default:
	break;
} // end type switch
?>	

<p class="header1"><?php echo _('Flag song'); ?></p>
<p><?php echo _('Flag the following song as having one of the problems listed below.  Site admins will then take the appropriate action for the flagged files.'); ?></p>
	
<form name="flag" method="post" action="<?php echo $web_path; ?>/flag.php" enctype="multipart/form-data">
<table class="text-box">
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('File'); ?>:</td>
	<td><?php echo $file; ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Item'); ?>:</td>
	<td><strong><?php echo $title; ?></strong></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Reason to flag'); ?>:</td>
	<td>
		<select name="flag_type">
			<option value="delete"><?php echo _('Delete'); ?></option>
			<option value="retag"><?php echo _('Incorrect Tags'); ?></option>
			<option value="reencode"><?php echo _('Re-encode'); ?></option>
			<option value="other"><?php echo _('Other'); ?></option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Comment'); ?>:</td>
	<td><input name="comment" type="text" size="50" maxlength="128" value="" /></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td> &nbsp; </td>
	<td>
		<input type="submit" value="<?php echo _('Flag'); ?>" />
		<input type="hidden" name="id" value="<?php echo scrub_out($_REQUEST['id']); ?>" />
		<input type="hidden" name="action" value="flag" />
		<input type="hidden" name="type" value="<?php echo scrub_out($type); ?>" />
	</td>
</tr>
</table>
</form>
<?php 
// NOT USED YET!
if ($type == 'pigsfly') { 
//elseif ($type == 'show_flagged_songs') {
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
?php 	} else { ?>
<p> You don't have any flagged songs. </p>
<?php	} // end if ($flags) and else
} // end elseif ($type == 'show_flagged_songs')
?>
