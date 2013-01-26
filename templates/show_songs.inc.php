<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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

$web_path = Config::get('web_path');
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_song" />
  <col id="col_artist" />
  <col id="col_album" />
  <col id="col_track" />
  <col id="col_time" />
  <col id="col_rating" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_add"><?php echo T_('Add'); ?></th>
	<th class="cel_song"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Song Title'), 'sort_song_title'); ?></th>
	<th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist', T_('Artist'), 'sort_song_artist'); ?></th>
	<th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album', T_('Album'), 'sort_song_album'); ?></th>
        <th class="cel_tags"><?php echo T_('Tags'); ?></th>
	<th class="cel_track"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=track', T_('Track'), 'sort_song_track'); ?></th>
	<th class="cel_time"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time', T_('Time'), 'sort_song_time'); ?></th>
<?php if (Config::get('ratings')) {
	Rating::build_cache('song', $object_ids);
?>
	<th class="cel_rating"><?php echo T_('Rating'); ?></th>
<?php } ?>
	<th class="cel_action"><?php echo T_('Action'); ?></th>
</tr>
<?php
	foreach ($object_ids as $song_id) {
		$song = new Song($song_id);
		$song->format();
?>
<tr class="<?php echo UI::flip_class(); ?>" id="song_<?php echo $song->id; ?>">
	<?php require Config::get('prefix') . '/templates/show_song_row.inc.php'; ?>
</tr>
<?php } ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo UI::flip_class(); ?>">
	<td colspan="9"><span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_add"><?php echo T_('Add'); ?></th>
	<th class="cel_song"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=title', T_('Song Title'),'sort_song_title_bottom'); ?></th>
	<th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=artist', T_('Artist'),'sort_song_artist_bottom'); ?></th>
	<th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=album', T_('Album'),'sort_song_album_bottom'); ?></th>
	<th class="cel_tags"><?php echo T_('Tags'); ?></th>
	<th class="cel_track"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=track', T_('Track'),'sort_song_track_bottom'); ?></th>
	<th class="cel_time"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=time', T_('Time'),'sort_song_time_bottom'); ?></th>
<?php if (Config::get('ratings')) { ?>
	<th class="cel_rating"><?php echo T_('Rating'); ?></th>
<?php } ?>
	<th class="cel_action"><?php echo T_('Action'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
