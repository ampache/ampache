<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

<ul>
    <li>
        <a href="javascript:void(0);" id="rb_append_dplaylist_new" onclick="handlePlaylistAction('<?php echo AmpConfig::get('ajax_url').'?page=playlist&action=append_item&item_type='.$object_type.'&item_id='.$object_id; ?>', 'rb_append_dplaylist_new');">
            <?php echo T_('Add to New Playlist'); ?>
        </a>
    </li>
<?php
    $playlists = Playlist::get_users($GLOBALS['user']->id);
    Playlist::build_cache($playlists);
    foreach ($playlists as $playlist_id) {
        $playlist = new Playlist($playlist_id);
        $playlist->format();
?>
    <li>
        <a href="javascript:void(0);" id="rb_append_dplaylist_<?php echo $playlist->id; ?>" onclick="handlePlaylistAction('<?php echo AmpConfig::get('ajax_url').'?page=playlist&action=append_item&playlist_id='.$playlist->id.'&item_type='.$object_type.'&item_id='.$object_id; ?>', 'rb_append_dplaylist_<?php echo $playlist->id; ?>');">
            <?php echo $playlist->f_name; ?>
        </a>
    </li>
<?php } ?>
</ul>
