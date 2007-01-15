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

/**
 * Show Genres
 * Takes an array of genre objects and displays them out
 */
$total_items = $view->total_items;
?>
<?php require(conf('prefix') . '/templates/show_box_top.inc.php'); ?>
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header" align="center">
	<td colspan="5">
		<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
<tr class="table-header">
	<td><?php echo _('Genre'); ?></td>
	<td><?php echo _('Songs'); ?></td>
	<td><?php echo _('Action'); ?></td>
</tr>
<?php 
foreach ($genres as $genre) { 
	$genre->format_genre();?>
	<tr class="<?php echo flip_class(); ?>">
		<td><?php echo $genre->link; ?></td>
		<td><?php echo $genre->get_song_count(); ?></td>
		<td>
			<a href="<?php echo $genre->play_link; ?>">
				<?php echo get_user_icon('all'); ?>
			</a> 
			<a href="<?php echo $genre->random_link; ?>">
				<?php echo get_user_icon('random'); ?>
			</a>
			<?php if (batch_ok()) { ?>
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
<?php require(conf('prefix') . '/templates/show_box_bottom.inc.php'); ?>
