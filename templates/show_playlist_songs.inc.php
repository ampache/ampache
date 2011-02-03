<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Playlist Songs
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

// First let's setup some vars we're going to use a lot
$web_path = Config::get('web_path');
$ajax_url = Config::get('ajax_url');
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_track" />
  <col id="col_song" />
  <col id="col_artist" />
  <col id="col_album" />
  <col id="col_genre" />
  <col id="col_track" />
  <col id="col_time" />
  <col id="col_rating" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_add">&nbsp;</th>
	<th class="cel_track"><?php echo _('Track'); ?></th>
	<th class="cel_song"><?php echo _('Song Title'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_genre"><?php echo _('Genre'); ?></th>
	<th class="cel_track"><?php echo _('Track'); ?></th>
	<th class="cel_time"><?php echo _('Time'); ?></th>
<?php if (Config::get('ratings')) {
        Rating::build_cache('song', array_map(create_function('$i', 'return $i[\'object_id\'];'), $object_ids));
?>
        <th class="cel_rating"><?php echo _('Rating'); ?></th>
<?php } ?>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
<?php
	foreach ($object_ids as $object) {
		$song = new Song($object['object_id']);
		$song->format();
		$playlist_track = $object['track'];
?>
<tr class="<?php echo flip_class(); ?>" id="track_<?php echo $object['track_id']; ?>">
	<?php require Config::get('prefix') . '/templates/show_playlist_song_row.inc.php'; ?>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_add">&nbsp;</th>
	<th class="cel_track"><?php echo _('Track'); ?></th>
	<th class="cel_song"><?php echo _('Song Title'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_genre"><?php echo _('Genre'); ?></th>
	<th class="cel_track"><?php echo _('Track'); ?></th>
	<th class="cel_time"><?php echo _('Time'); ?></th>
<?php if (Config::get('ratings')) { ?>
        <th class="cel_rating"><?php echo _('Rating'); ?></th>
<?php } ?>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
