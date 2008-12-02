<?php
/*

 Copyright (c) Ampache.org
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
?>
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
	foreach ($duplicates as $item) {
		// Gather the duplicates
		$songs = Catalog::get_duplicate_info($item,$search_type);

		foreach ($songs as $key=>$song_id) {
			$song = new Song($song_id); 
			$song->format(); 
			$row_key = 'duplicate_' . $song_id;
			$button_flip_state_id = 'button_flip_state_' . $song_id;
			$current_class = ($key == '0') ? 'row-highlight' : flip_class(); 
			$button = $song->enabled ? 'disable' : 'enable'; 
		?>
<tr id="<?php echo $row_key; ?>" class="<?php echo $current_class; ?>">
	<td class="cel_disable" id="<?php echo($button_flip_state_id); ?>">
		<?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $song_id,$button,_(ucfirst($button)),'flip_state_' . $song_id); ?>
	</td>
	<td class="cel_song"><?php echo $song->f_link; ?></td>
	<td class="cel_artist"><?php echo $song->f_artist_link; ?></td>
	<td class="cel_album"><?php echo $song->f_album_link; ?></td>
	<td class="cel_length"><?php echo $song->f_time; ?></td>
	<td class="cel_bitrate"><?php echo $song->f_bitrate; ?></td>
	<td class="cel_size"><?php echo $song->f_size; ?>MB</td>
	<td class="cel_filename"><?php echo scrub_out($song->file); ?></td>
</tr>
<?php 
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
</form>
<?php show_box_bottom(); ?>
