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
<td>
	<?php echo Ajax::button('?action=basket&type=album&id=' . $album->id,'add',_('Add'),'add_album_' . $album->id); ?>
	<?php echo Ajax::button('?action=basket&type=album_random&id=' . $album->id,'random',_('Random'),'random_album_' . $album->id); ?>
</td>
<td><?php echo $album->f_name_link; ?></td>
<td><?php echo $album->f_artist; ?></td>
<td><?php echo $album->song_count; ?></td>
<td><?php echo $album->year; ?></td>
<td>
	<?php if (Access::check_function('batch_download')) { ?>
		<a href="<?php echo Config::get('web_path'); ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>">
			<?php echo get_user_icon('batch_download',_('Batch Download')); ?>
		</a>
	<?php } ?>
	<?php if ($GLOBALS['user']->has_access('50')) { ?>
		<?php echo Ajax::button('?action=show_edit_object&type=album&id=' . $album->id,'edit',_('Edit'),'edit_album_' . $album->id); ?>
	<?php } ?>
</td>
