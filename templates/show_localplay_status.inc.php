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
 */

$status      = $localplay->status();
$now_playing = $status['track_title'];
if (!empty($status['track_album'])) {
    $now_playing .= ' - ' . $status['track_album'] . ' - ' . $status['track_artist'];
} ?>
<?php Ajax::start_container('localplay_status'); ?>
<?php UI::show_box_top(T_('Localplay Control') . ' - ' . strtoupper($localplay->type), 'box box_localplay_status'); ?>
<?php echo T_('Now Playing'); ?>:&nbsp;<i><?php echo $now_playing; ?></i>
<div id="information_actions">
    <ul>
        <li>
        <?php echo T_('Volume'); ?>: <?php echo $status['volume']; ?>%
        </li>
        <li>
            <?php echo print_bool($status['repeat']); ?> |
            <?php echo Ajax::text('?page=localplay&action=repeat&value=' . invert_bool($status['repeat']), print_bool(invert_bool($status['repeat'])), 'localplay_repeat'); ?>
            <?php echo T_('Repeat'); ?>
        </li>
        <li>
            <?php echo print_bool($status['random']); ?> |
            <?php echo Ajax::text('?page=localplay&action=random&value=' . invert_bool($status['random']), print_bool(invert_bool($status['random'])), 'localplay_random'); ?>
            <?php echo T_('Random'); ?>
        </li>
        <li>
            <?php echo Ajax::button('?page=localplay&action=command&command=delete_all', 'delete', T_('Clear Playlist'), 'localplay_clear_all'); ?><?php echo T_('Clear Playlist'); ?>
        </li>
    </ul>
</div>

<?php
    $browse = new Browse();
    $browse->set_type('playlist_localplay');
    $browse->set_static_content(true);
    $browse->show_objects($objects);
    $browse->store(); ?>
<?php UI::show_box_bottom(); ?>
<?php Ajax::end_container(); ?>
