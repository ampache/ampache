<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

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
<table class="border" cellspacing="0" cellpadding="0" border="0">
<tr class="even" align="center">
	<td colspan="5">
		<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
<tr class="table-header">
	<td><?php echo _("Genre"); ?></td>
	<td><?php echo _("Songs"); ?></td>
	<td><?php echo _("Action"); ?></td>
</tr>
<?php 
foreach ($genres as $genre) { 
	$genre->format_genre();?>
	<tr class="<?php echo flip_class(); ?>">
		<td><?php echo $genre->link; ?></td>
		<td><?php echo $genre->get_song_count(); ?></td>
		<td>
			<?php echo _("Play"); ?>:
			<a href="<?php echo $genre->play_link; ?>">All</a> 
			|
			<a href="<?php echo $genre->random_link; ?>">Random</a>
			|
			Download
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
