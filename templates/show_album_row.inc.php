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
<td class="cel_add">
	<?php echo Ajax::button('?action=basket&type=album&id=' . $album->id,'add',_('Add'),'add_album_' . $album->id); ?>
	<?php echo Ajax::button('?action=basket&type=album_random&id=' . $album->id,'random',_('Random'),'random_album_' . $album->id); ?>
</td>
<?php
  if (Config::get('bandwidth') > BANDWIDTH_LOW) {
  $name = '[' . $album->f_artist . '] ' . scrub_out($album->full_name);
  
  // This should not be hard coded, but it will likely break all the themes. 
  //$image_size = Config::get('bandwidth') == BANDWIDTH_MEDIUM ? 75 : 128;
  $image_size = 75;
?>
<td class="cel_cover">
        <a href="<?php echo Config::get('web_path'); ?>/albums.php?action=show&amp;album=<?php echo $album->id; ?>">
                <img height="<?php echo $image_size; ?>" width="<?php echo $image_size; ?>" alt="<?php echo($name) ?>" title="<?php echo($name) ?>" src="<?php echo Config::get('web_path'); ?>/image.php?id=<?php echo $album->id; ?>&amp;thumb=<?php echo Config::get('bandwidth'); ?>" />
        </a>
</td>
<?php } ?>
<td class="cel_album"><?php echo $album->f_name_link; ?></td>
<td class="cel_artist"><?php echo $album->f_artist_link; ?></td>
<td class="cel_songs"><?php echo $album->song_count; ?></td>
<td class="cel_year"><?php echo $album->year; ?></td>
<td class="cel_tags"><?php echo $album->f_tags; ?></td>
<td class="cel_rating" id="rating_<?php echo $album->id; ?>_album"><?php Rating::show($album->id,'album'); ?></td>
<td class="cel_action">
	<a href="<?php echo Config::get('web_path'); ?>/shout.php?action=show_add_shout&amp;type=album&amp;id=<?php echo $album->id; ?>">
		<?php echo get_user_icon('comment',_('Post Shout')); ?>
	</a>
	<?php if (Access::check_function('batch_download')) { ?>
		<a href="<?php echo Config::get('web_path'); ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>">
			<?php echo get_user_icon('batch_download',_('Batch Download')); ?>
		</a>
	<?php } ?>
	<?php if (Access::check('interface','50')) { ?>
		<?php echo Ajax::button('?action=show_edit_object&type=album&id=' . $album->id,'edit',_('Edit'),'edit_album_' . $album->id); ?>
	<?php } ?>
</td>
