<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php' ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_playlist" />
  <col id="col_type" />
  <col id="col_songs" />
  <col id="col_owner" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
  <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_playlist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=name', T_('Playlist Name'),'playlist_sort_name'); ?></th>
    <th class="cel_type">&nbsp;</th>
    <th class="cel_songs"><?php echo T_('# Songs'); ?></th>
    <th class="cel_owner"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=user', T_('Owner'),'playlist_sort_owner'); ?></th>
    <th class="cel_action"><?php echo T_('Actions'); ?></th>
</tr>
<?php
foreach ($object_ids as $playlist_id) {
    $playlist = new Playlist($playlist_id);
    $playlist->format();
    $count = $playlist->get_song_count();
?>
<tr class="<?php echo UI::flip_class(); ?>" id="playlist_row_<?php echo $playlist->id; ?>">
    <?php require Config::get('prefix') . '/templates/show_playlist_row.inc.php'; ?>
</tr>
<?php } // end foreach ($playlists as $playlist) ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td colspan="6"><span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
  <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_playlist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=name', T_('Playlist Name'),'playlist_sort_name_bottom'); ?></th>
    <th class="cel_type">&nbsp;</th>
    <th class="cel_songs"><?php echo T_('# Songs'); ?></th>
    <th class="cel_owner"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=user', T_('Owner'),'playlist_sort_owner_bottom'); ?></th>
    <th class="cel_action"><?php echo T_('Actions'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php' ?>
