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

$web_path = Config::get('web_path');
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<form method="post" id="reorder_playlist_<?php echo $playlist->id; ?>">
    <table id="reorder_playlist_table" class="tabledata" cellpadding="0" cellspacing="0">
        <tr class="th-top">
        <?php if (Config::get('directplay')) { ?>
            <th class="cel_directplay"><?php echo T_('Play'); ?></th>
        <?php } ?>
            <th class="cel_add"><?php echo T_('Add'); ?></th>
            <th class="cel_track"><?php echo T_('Track'); ?></th>
            <th class="cel_song"><?php echo T_('Song Title'); ?></th>
            <th class="cel_artist"><?php echo T_('Artist'); ?></th>
            <th class="cel_album"><?php echo T_('Album'); ?></th>
            <th class="cel_genre"><?php echo T_('Genre'); ?></th>
            <th class="cel_time"><?php echo T_('Time'); ?></th>
        <?php if (Config::get('ratings')) {
            Rating::build_cache('song', array_map(create_function('$i', 'return $i[\'object_id\'];'), $object_ids));
        ?>
            <th class="cel_rating"><?php echo T_('Rating'); ?></th>
        <?php } ?>
        <?php if (Config::get('userflags')) {
            Userflag::build_cache('song', array_map(create_function('$i', 'return $i[\'object_id\'];'), $object_ids));
        ?>
            <th class="cel_userflag"><?php echo T_('Flag'); ?></th>
        <?php } ?>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
            <th class="cel_drag"></th>
        </tr>

        <tbody id="sortableplaylist">
            <?php foreach ($object_ids as $object) {
                    $song = new Song($object['object_id']);
                    $song->format();
                    $playlist_track = $object['track'];
            ?>
                    <tr class="<?php echo UI::flip_class(); ?>" id="track_<?php echo $object['track_id']; ?>">
                        <?php require Config::get('prefix') . '/templates/show_playlist_song_row.inc.php'; ?>
                    </tr>
            <?php } ?>
        </tbody>

        <tr class="th-bottom">
        <?php if (Config::get('directplay')) { ?>
            <th class="cel_directplay"><?php echo T_('Play'); ?></th>
        <?php } ?>
            <th class="cel_add"><?php echo T_('Add'); ?></th>
            <th class="cel_track"><?php echo T_('Track'); ?></th>
            <th class="cel_song"><?php echo T_('Song Title'); ?></th>
            <th class="cel_artist"><?php echo T_('Artist'); ?></th>
            <th class="cel_album"><?php echo T_('Album'); ?></th>
            <th class="cel_genre"><?php echo T_('Genre'); ?></th>
            <th class="cel_time"><?php echo T_('Time'); ?></th>
        <?php if (Config::get('ratings')) { ?>
            <th class="cel_rating"><?php echo T_('Rating'); ?></th>
        <?php } ?>
        <?php if (Config::get('userflags')) { ?>
            <th class="cel_userflag"><?php echo T_('Flag'); ?></th>
        <?php } ?>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
            <th class="cel_drag"></th>
        </tr>
    </table>
</form>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
