<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
<?php show_box_top($song->title . ' ' . _('Details')); ?>
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
	<td><?php echo _('Title'); ?></td>
	<td><?php echo scrub_out($song->title); ?></td>
</tr>
<tr>
	<td><?php echo _('Artist'); ?></td>
	<td><?php echo $song->f_artist_link; ?></td>
</tr>
<tr>
	<td><?php echo _('Album'); ?></td>
	<td><?php echo $song->f_album_link; ?> (<?php echo scrub_out($song->year); ?>)</td>
</tr>
<tr>
	<td><?php echo _('Genre'); ?></td>
	<td><?php echo $song->f_genre_link; ?></td>
</tr>
<tr>
	<td><?php echo _('Bitrate'); ?></td>
	<td><?php echo scrub_out($song->f_bitrate); ?></td>
</tr>
<tr>
	<td><?php echo _('Filename'); ?></td>
	<td><?php echo scrub_out($song->file); ?> (<?php echo $song->f_size; ?>MB)</td>
</tr>
<?php if ($song->update_time) { ?>
<tr>
	<td><?php echo _('Last Updated'); ?></td>
	<td><?php echo date("d/m/Y H:i",$song->update_time); ?></td>
</tr>
<?php } ?>
<tr>
	<td><?php echo _('Added'); ?></td>
	<td><?php echo date("d/m/Y H:i",$song->addition_time); ?></td>
</table>
<?php show_box_bottom(); ?>
