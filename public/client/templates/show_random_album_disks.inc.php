<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var int[] $albumDisks */

$web_path = AmpConfig::get_web_path('/client');
$button   = Ajax::button('?page=index&action=random_albums', 'refresh', T_('Refresh'), 'random_refresh'); ?>
<?php Ui::show_box_top(T_('Albums of the Moment') . ' ' . $button, 'box box_random_albums'); ?>
<?php
if (!empty($albumDisks)) {
    foreach ($albumDisks as $album_disk_id) {
        $albumDisk = new AlbumDisk($album_disk_id);
        $show_play = true; ?>
    <div class="random_album">
        <div id="album_<?php echo $album_disk_id; ?>" class="art_album libitem_menu">
            <?php $size = ['width' => 100, 'height' => 100];
        if (Ui::is_grid_view('album')) {
            $size      = ['width' => 150, 'height' => 150];
            $show_play = false;
        }
        $albumDisk->display_art($size, true); ?>
        </div>
        <?php if ($show_play) { ?>
        <div class="play_album">
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id, 'play_circle', T_('Play'), 'play_album_disk_' . $albumDisk->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_album_disk_' . $albumDisk->id); ?>
                <?php } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_album_disk_' . $albumDisk->id); ?>
            <?php } ?>
        <?php } ?>
        <?php echo Ajax::button('?action=basket&type=album_disk&id=' . $albumDisk->id, 'new_window', T_('Add to Temporary Playlist'), 'play_full_' . $albumDisk->id); ?>
        </div>
        <?php } ?>
        <?php
        if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $albumDisk->id; ?>_album"><?php echo Rating::show($albumDisk->id, 'album_disk'); ?></span>
                <span class="cel_rating" id="userflag_<?php echo $albumDisk->id; ?>_album"><?php echo Userflag::show($albumDisk->id, 'album_disk'); ?></span>
            <?php } ?>
        <?php } ?>
    </div>
    <?php
    } ?>
<?php
} ?>

<?php Ui::show_box_bottom(); ?>
