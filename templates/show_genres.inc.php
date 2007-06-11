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
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header" align="center">
	<td colspan="5">
		<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
<tr class="table-header">
	<td><?php echo _('Add'); ?></td>
	<td><?php echo _('Genre'); ?></td>
	<td><?php echo _('Songs'); ?></td>
	<td><?php echo _('Action'); ?></td>
</tr>
<?php 
foreach ($object_ids as $genre_id) { 
	$genre = new Genre($genre_id); 
	$genre->format();
?>
	<tr class="<?php echo flip_class(); ?>">
		<td>
		<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=genre&amp;id=<?php echo $genre->id; ?>');return true;" >
				<?php echo get_user_icon('add'); ?>
		</span>
		<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=genre_random&amp;id=<?php echo $genre->id; ?>');return true;" >
				<?php echo get_user_icon('random'); ?>
		</span>
		</td>
		<td><?php echo $genre->link; ?></td>
		<td><?php echo $genre->get_song_count(); ?></td>
		<td>
			<?php if (Access::check_function('batch_download')) { ?>
			<a href="<?php echo $genre->download_link; ?>">
				<?php echo get_user_icon('batch_download'); ?>
			</a>
			<?php } ?>
		</td>
	</tr>
<?php } // end foreach genres ?>
<tr class="even" align="center">
	<td colspan="5">
		<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
</table>
