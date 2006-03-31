<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/*!
	@header Show Artists
	Shows multiple artists.... takes an array of artist objects

*/
$web_path = conf('web_path');

// Build array of the table classes we are using
$total_items = $view->total_items;
?>
<table class="border" cellspacing="0" cellpadding="0" border="0">
<tr class="even" align="center">
	<td colspan="5">
	<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
<tr class="table-header">
	<td>
		<a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=artist.name&amp;sort_order=0"> <?php echo _("Artist"); ?> </a>
	</td>
	<td> <?php echo _('Songs');  ?> </td>
	<td> <?php echo _('Albums'); ?> </td>
	<td> <?php echo _('Action'); ?> </td>
</tr>
<?php 
/* Foreach through every artist that has been passed to us */
//FIXME: These should come in as objects...
foreach ($artists as $artist) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td><?php echo $artist['name']; ?></td>
		<td><?php echo $artist['songs']; ?></td>
		<td><?php echo $artist['albums']; ?></td>	
		<td nowrap="nowrap"> <?php echo _("Play"); ?> : 
			<a href="<?php echo $web_path; ?>/song.php?action=artist&amp;artist_id=<?php echo $artist['id']; ?>"><?php echo _("All"); ?></a> | 
			<a href="<?php echo $web_path; ?>/song.php?action=artist_random&amp;artist_id=<?php echo $artist['id']; ?>"><?php echo _("Random"); ?></a> 
		</td>
	</tr>
<?php } //end foreach ($artists as $artist) ?>
<tr class="table-header">
        <td>
                <a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=artist.name&amp;sort_order=0"> <?php echo _("Artist"); ?> </a>
        </td>
        <td><?php echo _('Songs');  ?></td>
        <td><?php echo _('Albums'); ?></td>
	<td><?php echo _('Action'); ?></td>

</tr>
<tr class="even" align="center">
	<td colspan="4">
	<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
</table>

