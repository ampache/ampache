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
$web_path = Config::get('web_path');
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_action" />
  <col id="col_votes" />
  <col id="col_title" />
  <col id="col_album" />
  <col id="col_artist" />
  <col id="col_time" />
  <?php if (Access::check('interface','100')) { ?>
  <col id="col_admin" />
  <?php } ?>
</colgroup>
<?php
if (!count($object_ids)) { 
	$playlist = new Playlist($democratic->base_playlist);
?>
<tr>
<td>
	<?php echo _('Playing from base Playlist'); ?>: 
	<a href="<?php echo $web_path; ?>/playlist.php?action=show_playlist&amp;playlist_id=<?php echo $playlist->id; ?>">
	<?php echo scrub_out($playlist->name); ?>
	</a>
</td>
</tr>
<?php
} // if no songs
/* Else we have songs */
else {
?>
<tr class="th-top">
	<th class="cel_action"><?php echo _('Action'); ?></th>
	<th class="cel_votes"><?php echo _('Votes'); ?></th>
	<th class="cel_title"><?php echo _('Title'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_time"><?php echo _('Time'); ?></th>
	<?php if (Access::check('interface','100')) { ?>
	<th class="cel_admin"><?php echo _('Admin'); ?></th>
	<?php } ?>
</tr>
<?php 
$democratic = Democratic::get_current_playlist(); 
$democratic->set_parent(); 
foreach($object_ids as $row_id=>$data) { 
	$media = new $data['object_type']($data['object_id']);
	$media->format();
?>
<tr class="<?php echo flip_class(); ?>">
	<td class="cel_action">
	<?php if ($democratic->has_vote($media->id)) { ?>
	<?php echo Ajax::button('?page=democratic&action=delete_vote&row_id=' . $row_id,'delete',_('Remove Vote'),'remove_vote_' . $row_id); ?>
	<?php } else { ?>
	<?php echo Ajax::button('?page=democratic&action=add_vote&object_id=' . $media->id . '&type=' . scrub_out($data['object_type']),'tick',_('Add Vote'),'remove_vote_' . $row_id); ?>
	<?php } ?>
	</td>
	<td class="cel_votes" ><?php echo scrub_out($democratic->get_vote($row_id)); ?></td>
	<td class="cel_title"><?php echo $media->f_link; ?></td>
	<td class="cel_album"><?php echo $media->f_album_link; ?></td>
	<td class="cel_artist"><?php echo $media->f_artist_link; ?></td>
	<td class="cel_time"><?php echo $media->f_time; ?></td>
	<?php if (Access::check('interface','100')) { ?>
	<td class="cel_admin">
	<?php echo Ajax::button('?page=democratic&action=delete&row_id=' . $row_id,'disable',_('Delete'),'delete_row_' . $row_id); ?>
	</td>
	<?php } ?>
</tr>
<?php 
	} // end foreach
?> 
<tr class="th-bottom">
	<th class="cel_action"><?php echo _('Action'); ?></th>
	<th class="cel_votes"><?php echo _('Votes'); ?></th>
	<th class="cel_title"><?php echo _('Title'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_time"><?php echo _('Time'); ?></th>
	<?php if (Access::check('interface','100')) { ?>
	<th class="cel_admin"><?php echo _('Admin'); ?></th>
	<?php } ?>
</tr>
<?php
} // end else
?>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
