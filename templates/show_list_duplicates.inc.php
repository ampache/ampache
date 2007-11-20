<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

$web_path = conf('web_path');
show_duplicate_searchbox($search_type);

if (count($flags)) { ?>
	<?php show_box_top(_('Duplicate Songs')); ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/admin/flag.php?action=disable">
  <table class="tabledata" cellpadding="0" cellspacing="0">
  <colgroup>
    <col id="col_disable" />
    <col id="col_song" />
    <col id="col_artist" />
    <col id="col_album" />
    <col id="col_length" />
    <col id="col_bitrate" />
    <col id="col_size" />
    <col id="col_filename" />
  </colgroup>	
	<tr class="th-top">
		<th class="cel_disable"><?php echo _('Disable'); ?></th>
		<th class="cel_song"><?php echo _('Song'); ?></th>
		<th class="cel_artist"><?php echo _('Artist'); ?></th>
		<th class="cel_album"><?php echo _('Album'); ?></th>
		<th class="cel_length"><?php echo _('Length'); ?></th>
		<th class="cel_bitrate"><?php echo _('Bitrate'); ?></th>
		<th class="cel_size"><?php echo _('Size'); ?></th>
		<th class="cel_filename"><?php echo _('Filename'); ?></th>
	</tr>
	<?php 
	foreach ($flags as $flag) {
		/* Build the Song */
		$song = new Song($flag['song']);
		$song->format_song();

		// Set some extra vars
		$alt_title = $song->title;
		$formated_title = $song->f_title;
		$artist = $song->f_artist;
		$alt_artist = $song->f_full_artist;

		// Gather the duplicates
		$dinfolist = get_duplicate_info($song,$search_type,$_REQUEST['auto']);

		// Set the current class, only changes once per set of duplicates
		$current_class = flip_class(); 

		foreach ($dinfolist as $key=>$dinfo) {
			$check_txt = '';
			if ($key == '0' AND $_REQUEST['auto']) { $check_txt = ' checked="checked"'; } 
			echo "<tr class=\"".$current_class."\">".
			"<td class=\"cel_disable\"><input type=\"checkbox\" name=\"song_ids[]\" value=\"" . $dinfo['songid'] . "\" $check_txt/></td>".
			"<td class=\"cel_song\"><a href=\"$web_path/stream.php?action=single_song&amp;song_id=$song->id\">".scrub_out($formated_title)."</a> </td>".
			"<td class=\"cel_artist\"><a href=\"$web_path/artists.php?action=show&amp;artist=".$dinfo['artistid']."\" title=\"".scrub_out($dinfo['artist'])."\">".scrub_out($dinfo['artist'])."</a> </td>".
			"<td class=\"cel_album\"><a href=\"$web_path/albums.php?action=show&amp;album=".$dinfo['albumid']."\" title=\"".scrub_out($dinfo['album'])."\">".scrub_out($dinfo['album'])."</a> </td>".
			"<td class=\"cel_length\">".floor($dinfo['time']/60).":".sprintf("%02d", ($dinfo['time']%60) )."</td>".
			"<td class=\"cel_bitrate\">".intval($dinfo['bitrate']/1000)."</td>".
			"<td class=\"cel_size\">".sprintf("%.2f", ($dinfo['size']/1048576))."MB</td>".
			"<td class=\"cel_filename\">".$dinfo['file']."</td>";
			echo "</tr>\n";
		} // end foreach ($dinfolist as $dinfo)
		
	} // end foreach ($flags as $flag)
	?>
	<tr class="th-bottom">
		<th class="cel_disable"><?php echo _('Disable'); ?></th>
		<th class="cel_song"><?php echo _('Song'); ?></th>
		<th class="cel_artist"><?php echo _('Artist'); ?></th>
		<th class="cel_album"><?php echo _('Album'); ?></th>
		<th class="cel_length"><?php echo _('Length'); ?></th>
		<th class="cel_bitrate"><?php echo _('Bitrate'); ?></th>
		<th class="cel_size"><?php echo _('Size'); ?></th>
		<th class="cel_filename"><?php echo _('Filename'); ?></th>
	</tr>
	</table>
	<div class="formValidation">
    <input type="submit" value="<?php echo _('Disable Songs'); ?>" />
  </div>
	</form>
	<?php show_box_bottom(); ?>
<?php  } else { ?>
<p class="error"><?php echo _('No Records Found'); ?></p>
<?php  } // end if ($flags) and else ?>

