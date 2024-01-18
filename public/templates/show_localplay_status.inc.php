<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Module\Api\Ajax;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Util\Ui;

/** @var Localplay $localplay */
/** @var array $objects */

Ajax::start_container('localplay_status');
Ui::show_box_top(T_('Localplay Control') . ' - ' . strtoupper($localplay->type), 'box box_localplay_status');
$status = $localplay->status();
if (!empty($status)) {
    $now_playing = $status['track_title'] ?? '';
    if (!empty($status['track_album'])) {
        $now_playing .= ' - ' . $status['track_album'];
    }
    if (!empty($status['track_artist'])) {
        $now_playing .= ' - ' . $status['track_artist'];
    } ?>
<?php echo T_('Now Playing'); ?>:&nbsp;<i><?php echo $now_playing; ?></i>
<div id="information_actions">
    <ul>
        <li>
        <?php echo T_('Volume'); ?>: <?php echo $status['volume']; ?>%
        </li>
        <li>
            <?php echo Ui::printBool($status['repeat']); ?> |
            <?php echo Ajax::text('?page=localplay&action=repeat&value=' . invert_bool($status['repeat']), Ui::printBool(invert_bool($status['repeat'])), 'localplay_repeat'); ?>
            <?php echo T_('Repeat'); ?>
        </li>
        <li>
            <?php echo Ui::printBool($status['random']); ?> |
            <?php echo Ajax::text('?page=localplay&action=random&value=' . invert_bool($status['random']), Ui::printBool(invert_bool($status['random'])), 'localplay_random'); ?>
            <?php echo T_('Random'); ?>
        </li>
        <li>
            <?php echo Ajax::button('?page=localplay&action=command&command=delete_all', 'delete', T_('Clear Playlist'), 'localplay_clear_all'); ?><?php echo T_('Clear Playlist'); ?>
        </li>
    </ul>
</div>
<?php }
$browse = new Browse();
$browse->set_type('playlist_localplay');
$browse->set_use_filters(false);
$browse->set_static_content(true);
$browse->show_objects($objects);
$browse->store();
Ui::show_box_bottom();
Ajax::end_container(); ?>
