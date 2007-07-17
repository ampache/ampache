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
	<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=album&amp;id=<?php echo $album->id; ?>');return true;" >
		<?php echo get_user_icon('add','',_('Add')); ?>
	</span>
	<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=album_random&amp;id=<?php echo $album->id; ?>');return true;" >
		<?php echo get_user_icon('random','',_('Random')); ?>
	</span>
	</td>
	<td><?php echo $album->f_name_link; ?></td>
	<td><?php echo $album->f_artist; ?></td>
	<td><?php echo $album->song_count; ?></td>
	<td><?php echo $album->year; ?></td>
	<td>
	<?php if (Access::check_function('batch_download')) { ?>
		<a href="<?php echo Config::get('web_path'); ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>">
			<?php echo get_user_icon('batch_download','',_('Batch Download')); ?>
		</a>
	<?php } ?>
	<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=show_edit_object&amp;type=album&amp;id=<?php echo $album->id; ?>');return true;" >
			<?php echo get_user_icon('edit','',_('Edit')); ?>
	</span>
</td>
