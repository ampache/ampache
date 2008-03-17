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

/**
 * Show Genres
 * Takes an array of genre objects and displays them out
 */
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php' ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_genre" />
  <col id="col_songs" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_genre"><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Genre'),'sort_genre_name'); ?></th>
	<th class="cel_songs"><?php echo _('Songs'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
<?php 
foreach ($object_ids as $genre_id) { 
	$genre = new Genre($genre_id); 
	$genre->format();
?>
	<tr class="<?php echo flip_class(); ?>">
		<td class="cel_add">
		<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=genre&amp;id=<?php echo $genre->id; ?>');return true;" >
				<?php echo get_user_icon('add'); ?>
		</span>
		<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=genre_random&amp;id=<?php echo $genre->id; ?>');return true;" >
				<?php echo get_user_icon('random'); ?>
		</span>
		</td>
		<td class="cel_genre"><?php echo $genre->f_link; ?></td>
		<td class="cel_songs"><?php echo $genre->get_song_count(); ?></td>
		<td class="cel_action">
			<?php if (Access::check_function('batch_download')) { ?>
			<a href="<?php echo $genre->download_link; ?>">
				<?php echo get_user_icon('batch_download'); ?>
			</a>
			<?php } ?>
		</td>
	</tr>
<?php } // end foreach genres ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="4"><span class="fatalerror"><?php echo _('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_genre"><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Genre'),'sort_genre_name_bottom'); ?></th>
	<th class="cel_songs"><?php echo _('Songs'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php' ?>
