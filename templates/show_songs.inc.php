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

// First let's setup some vars we're going to use a lot
$web_path = Config::get('web_path'); 
$ajax_url = Config::get('ajax_url'); 
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
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_song"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=title',_('Song Title'),'sort_song_title'); ?></th>
	<th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=artist',_('Artist'),'sort_song_artist'); ?></th>
	<th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=album',_('Album'),'sort_song_album'); ?></th>
        <th class="cel_tags"><?php echo _('Tags'); ?></th>
	<th class="cel_track"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=track',_('Track'),'sort_song_track'); ?></th>
	<th class="cel_time"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=time',_('Time'),'sort_song_time'); ?></th>
<?php if (Config::get('ratings')) {
	Rating::build_cache('song', $object_ids);
?>
	<th class="cel_rating"><?php echo _('Rating'); ?></th>
<?php } ?>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
<?php
	foreach ($object_ids as $song_id) { 
		$song = new Song($song_id); 
		$song->format(); 
?>
<tr class="<?php echo flip_class(); ?>" id="song_<?php echo $song->id; ?>">
	<?php require Config::get('prefix') . '/templates/show_song_row.inc.php'; ?> 
</tr>
<?php } ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="9"><span class="fatalerror"><?php echo _('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_song"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=title',_('Song Title'),'sort_song_title_bottom'); ?></th>
	<th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=artist',_('Artist'),'sort_song_artist_bottom'); ?></th>
	<th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=album',_('Album'),'sort_song_album_bottom'); ?></th>
	<th class="cel_tags"><?php echo _('Tags'); ?></th>
	<th class="cel_track"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=track',_('Track'),'sort_song_track_bottom'); ?></th>
	<th class="cel_time"><?php echo Ajax::text('?page=browse&action=set_sort&type=song&sort=time',_('Time'),'sort_song_time_bottom'); ?></th>
<?php if (Config::get('ratings')) { ?>
	<th class="cel_rating"><?php echo _('Rating'); ?></th>
<?php } ?>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
