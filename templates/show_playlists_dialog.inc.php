<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>

<ul>
    <li>
        <a href="javascript:void(0);" id="rb_append_dplaylist_new" onclick="createNewPlaylist('<?php echo T_('Playlist Name'); ?>', '<?php echo AmpConfig::get('ajax_url') . '?page=playlist&action=append_item&item_type=' . $object_type . '&item_id=' . $object_id; ?>', 'rb_append_dplaylist_new');">
            <?php echo T_('Add to New Playlist'); ?>
        </a>
    </li>
<?php
    $playlists = Playlist::get_users(Core::get_global('user')->id);
    Playlist::build_cache($playlists);
    foreach ($playlists as $playlist_id) {
        $playlist = new Playlist($playlist_id); ?>
    <li>
        <a href="javascript:void(0);" id="rb_append_dplaylist_<?php echo $playlist->id; ?>" onclick="handlePlaylistAction('<?php echo AmpConfig::get('ajax_url') . '?page=playlist&action=append_item&playlist_id=' . $playlist->id . '&item_type=' . $object_type . '&item_id=' . $object_id; ?>', 'rb_append_dplaylist_<?php echo $playlist->id; ?>');">
            <?php echo $playlist->name; ?>
        </a>
    </li>
<?php
    } ?>
</ul>
