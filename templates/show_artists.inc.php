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

?>
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
	<td colspan="5">
	<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
	</td>
</tr>
<tr class="table-header">
	<th><?php echo _('Add'); ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Artist'),'artist_sort_name'); ?></th>
	<th> <?php echo _('Songs');  ?> </th>
	<th> <?php echo _('Albums'); ?> </th>
	<th> <?php echo _('Action'); ?> </th>
</tr>
<?php 
/* Foreach through every artist that has been passed to us */
foreach ($object_ids as $artist_id) { 
		$artist = new Artist($artist_id); 
		$artist->format(); 
?>
<tr id="artist_<?php echo $artist->id; ?>" class="<?php echo flip_class(); ?>">
	<?php require Config::get('prefix') . '/templates/show_artist_row.inc.php'; ?>
</tr>
<?php } //end foreach ($artists as $artist) ?>
<tr class="table-header">
	<th><?php echo _('Add'); ?></th>
        <th><?php echo _("Artist"); ?></th>
        <th><?php echo _('Songs');  ?></th>
        <th><?php echo _('Albums'); ?></th>
	<th><?php echo _('Action'); ?></th>

</tr>
<tr>
	<td colspan="5">
	<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
	</td>
</tr>
</table>
