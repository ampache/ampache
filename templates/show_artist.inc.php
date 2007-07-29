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

$web_path = Config::get('web_path');

require Config::get('prefix') . '/templates/show_artist_box.inc.php';

show_box_top(); 
?>
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header">
	<th align="center"><?php echo _('Add'); ?></th>
	<th><?php echo _('Cover'); ?></th>
	<th><?php echo _('Album Name'); ?></th>
	<th><?php echo _('Album Year'); ?></th>
	<th><?php echo _('Tracks'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php
foreach ($albums as $album_id) {
	$album = new Album($album_id);
	$album->format(); 
?>
<tr class="<?php echo flip_class(); ?>">
	<td align="center">
                <span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=album&amp;id=<?php echo $album->id; ?>');return true;" >
        	        <?php echo get_user_icon('add','',_('Add')); ?>
                </span>
                <span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=album_random&amp;id=<?php echo $album->id; ?>');return true;" >
	                <?php echo get_user_icon('random','',_('Random')); ?>
	        </span>
	</td>
	<td height="87">
	<a href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $album->id; ?>&amp;artist=<?php echo $artist->id; ?>">
		<img border="0" src="<?php echo $web_path; ?>/image.php?id=<?php echo $album->id; ?>&amp;thumb=1&amp;sid=<?php echo session_id(); ?>" alt="<?php echo scrub_out($album->name); ?>" title="<?php echo scrub_out($album->name); ?>" height="75" width="75" />
	</a>
	</td>
	<td>
		<?php echo $album->f_name_link; ?>
	</td>
	<td><?php echo $album->year; ?></td>
	<td><?php echo $album->song_count; ?></td>
	<td>
	<?php if (Access::check_function('batch_download')) { ?>
		<a href="<?php echo $web_path; ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>">
			<?php echo get_user_icon('batch_download'); ?>
		</a>
	<?php } ?> 
	</td>
</tr>
<?php  } //end foreach ($albums as $album)?>
</table>
<?php show_box_bottom(); ?>
