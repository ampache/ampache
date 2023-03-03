<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var int[] $albumDisks */

$web_path = AmpConfig::get('web_path');
$button   = Ajax::button('?page=index&action=random_albums', 'random', T_('Refresh'), 'random_refresh'); ?>
<?php Ui::show_box_top(T_('Albums of the Moment') . ' ' . $button, 'box box_random_albums'); ?>
<?php
if (!empty($albumDisks)) {
    foreach ($albumDisks as $album_disk_id) {
        $albumDisk = new AlbumDisk($album_disk_id);
        $albumDisk->format();
        $show_play = true; ?>
    <div class="random_album">
        <div id="album_<?php echo $album_disk_id ?>" class="art_album libitem_menu">
            <?php $thumb = 1;
        if (!Ui::is_grid_view('album')) {
            $thumb     = 11;
            $show_play = false;
        }
        $albumDisk->display_art($thumb, true); ?>
        </div>
        <?php if ($show_play) { ?>
        <div class="play_album">
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id, 'play', T_('Play'), 'play_album_disk_' . $albumDisk->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_album_disk_' . $albumDisk->id); ?>
                <?php } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id . '&append=true', 'play_add', T_('Play last'), 'addplay_album_disk_' . $albumDisk->id); ?>
            <?php } ?>
        <?php } ?>
        <?php echo Ajax::button('?action=basket&type=album_disk&id=' . $albumDisk->id, 'add', T_('Add to Temporary Playlist'), 'play_full_' . $albumDisk->id); ?>
        </div>
        <?php } ?>
        <?php
        if (Access::check('interface', 25)) { ?>
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
