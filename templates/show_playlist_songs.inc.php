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

$web_path = AmpConfig::get('web_path');
?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<form method="post" id="reorder_playlist_<?php echo $playlist->id; ?>">
    <table id="reorder_playlist_table" class="tabledata" cellpadding="0" cellspacing="0">
        <thead>
            <tr class="th-top">
                <th class="cel_play essential"></th>
                <th class="cel_song essential persist"><?php echo T_('Song Title'); ?></th>
                <th class="cel_add essential"></th>
                <th class="cel_artist essential"><?php echo T_('Artist'); ?></th>
                <th class="cel_album optional"><?php echo T_('Album'); ?></th>
                <th class="cel_tags optional"><?php echo T_('Tags'); ?></th>
                <th class="cel_time optional"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('ratings')) {
                Rating::build_cache('song', array_map(create_function('$i', '$i=(array) $i; return $i[\'object_id\'];'), $object_ids));
            ?>
                <th class="cel_rating"><?php echo T_('Rating'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('userflags')) {
                Userflag::build_cache('song', array_map(create_function('$i', '$i=(array) $i; return $i[\'object_id\'];'), $object_ids));
            ?>
                <th class="cel_userflag essential"><?php echo T_('Fav.'); ?></th>
            <?php } ?>
                <th class="cel_action essential"><?php echo T_('Action'); ?></th>
                <th class="cel_drag essential"></th>
            </tr>
        </thead>
        <tbody id="sortableplaylist_<?php echo $playlist->id; ?>">
            <?php foreach ($object_ids as $object) {
                    if (!is_array($object)) {
                        $object = (array) $object;
                    }
                    $libitem = new Song($object['object_id']);
                    $libitem->format();
                    $playlist_track = $object['track'];
            ?>
                    <tr class="<?php echo UI::flip_class(); ?>" id="track_<?php echo $object['track_id']; ?>">
                        <?php require AmpConfig::get('prefix') . '/templates/show_playlist_song_row.inc.php'; ?>
                    </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr class="th-bottom">
                <th class="cel_play"><?php echo T_('Play'); ?></th>
                <th class="cel_song"><?php echo T_('Song Title'); ?></th>
                <th class="cel_add"></th>
                <th class="cel_artist"><?php echo T_('Artist'); ?></th>
                <th class="cel_album"><?php echo T_('Album'); ?></th>
                <th class="cel_tags"><?php echo T_('Tags'); ?></th>
                <th class="cel_time"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('ratings')) { ?>
                <th class="cel_rating"><?php echo T_('Rating'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('userflags')) { ?>
                <th class="cel_userflag"><?php echo T_('Fav.'); ?></th>
            <?php } ?>
                <th class="cel_action"><?php echo T_('Action'); ?></th>
                <th class="cel_drag"></th>
            </tr>
        </tfoot>
    </table>
</form>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
