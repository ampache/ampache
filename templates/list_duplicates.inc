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

<?php
$web_path = conf('web_path');
show_duplicate_searchbox($search_type);
if ($flags) { ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo  $web_path . "/admin/song.php?action=disable"; ?>">
	<p class="header1"><?php echo _('Duplicate Songs'); ?></p>
	<table class="tabledata" cellspacing="0" cellpadding="0" >
	<tr class="table-header">
		<td><?php echo _('Disable'); ?></td>
		<td><?php echo _('Song'); ?></td>
		<td><?php echo _('Artist'); ?></td>
		<td><?php echo _('Album'); ?></td>
		<td><?php echo _('Length'); ?></td>
		<td><?php echo _('Bitrate'); ?></td>
		<td><?php echo _('Size'); ?></td>
		<td><?php echo _('Filename'); ?></td>
	</tr>
	<?php 
	foreach ($flags as $flag) {
		$song = new Song($flag['song']);
		$song->format_song();
		$alt_title = $song->title;
		$formated_title = $song->f_title;
		$artist = $song->f_artist;
		$alt_artist = $song->f_full_artist;
		$dinfolist = get_duplicate_info($song,$search_type);
		foreach ($dinfolist as $dinfo) {
			echo "<tr class=\"".flip_class()."\">".
			"<td><input type=\"checkbox\" name=\"song_ids[]\" value=\"" . $dinfo['songid'] . "\" /></td>".
			"<td><a href=\"".$web_path."/song.php?action=m3u&amp;song=$song->id\">".scrub_out($formated_title)."</a> </td>".
			"<td><a href=\"".$web_path."/artists.php?action=show&amp;artist=".$dinfo['artistid']."\" title=\"".scrub_out($dinfo['artist'])."\">".scrub_out($dinfo['artist'])."</a> </td>".
			"<td><a href=\"".$web_path."/albums.php?action=show&amp;album=".$dinfo['albumid']."\" title=\"".scrub_out($dinfo['album'])."\">".scrub_out($dinfo['album'])."</a> </td>".
			"<td>".floor($dinfo['time']/60).":".sprintf("%02d", ($dinfo['time']%60) )."</td>".
			"<td>".intval($dinfo['bitrate']/1000)."</td>".
			"<td>".sprintf("%.2f", ($dinfo['size']/1000000))."Mb</td>".
			"<td>".$dinfo['file']."</td>";
			echo "</tr>\n";
		} // end foreach ($dinfolist as $dinfo)
	} // end foreach ($flags as $flag)
	?>
	<tr>
		<td colspan="8" class="<?php echo flip_class(); ?>"><input height="15" type="submit" value="Disable Songs" /></td>
	</tr>
	</table>
	</form>
<?php  } else { ?>
<p><?php _('You don\'t have any duplicate songs.'); ?></p>
<?php  } // end if ($flags) and else ?>

