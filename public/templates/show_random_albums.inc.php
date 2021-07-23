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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

$web_path = AmpConfig::get('web_path');
$button   = Ajax::button('?page=index&action=random_albums', 'random', T_('Refresh'), 'random_refresh'); ?>
<?php Ui::show_box_top(T_('Albums of the Moment') . ' ' . $button, 'box box_random_albums'); ?>
<?php
if ($albums) {
    foreach ($albums as $album_id) {
        $album = new Album($album_id);
        $album->format();
        $show_play = true; ?>
    <div class="random_album">
        <div id="album_<?php echo $album_id ?>" class="art_album libitem_menu">
            <?php $thumb = 1;
        if (!Ui::is_grid_view('album')) {
            $thumb     = 11;
            $show_play = false;
        }
        $album->display_art($thumb, true); ?>
        </div>
        <?php if ($show_play) { ?>
        <div class="play_album">
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $album->get_http_album_query_ids('object_id'), 'play', T_('Play'), 'play_album_' . $album->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $album->get_http_album_query_ids('object_id') . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_album_' . $album->id); ?>
                <?php
            } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $album->get_http_album_query_ids('object_id') . '&append=true', 'play_add', T_('Play last'), 'addplay_album_' . $album->id); ?>
            <?php
            } ?>
        <?php
                } ?>
        <?php echo Ajax::button('?action=basket&type=album_full&' . $album->get_http_album_query_ids('id'), 'add', T_('Add to Temporary Playlist'), 'play_full_' . $album->id); ?>
        </div>
        <?php
            } ?>
        <?php
        if (Access::check('interface', 25)) { ?>
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $album->id; ?>_album"><?php echo Rating::show($album->id, 'album'); ?></span>
            <?php } ?>

            <?php if (AmpConfig::get('userflags')) { ?>
                <span class="cel_rating" id="userflag_<?php echo $album->id; ?>_album"><?php echo Userflag::show($album->id, 'album'); ?></span>
            <?php } ?>
        <?php } ?>
    </div>
    <?php
    } ?>
<?php
} ?>

<?php Ui::show_box_bottom(); ?>
