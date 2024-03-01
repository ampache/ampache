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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var int[] $videos */

$web_path = (string)AmpConfig::get('web_path', '');
$button   = Ajax::button('?page=index&action=random_videos', 'refresh', T_('Refresh'), 'random_video_refresh'); ?>
<?php Ui::show_box_top(T_('Videos of the Moment') . ' ' . $button, 'box box_random_videos'); ?>
<?php
if (!empty($videos)) {
    foreach ($videos as $video_id) {
        $video = Video::create_from_id($video_id);
        $video->format(); ?>
    <div class="random_video">
        <div id="video_<?php echo $video_id; ?>" class="art_album libitem_menu">
            <?php $art_showed = false;
        if ($video->get_default_art_kind() == 'preview') {
            $art_showed = Art::display('video', $video->id, $video->getFileName() ?? '', 9, $video->get_link(), false, 'preview');
        }
        if (!$art_showed) {
            $thumb = Ui::is_grid_view('video') ? 7 : 6;
            Art::display('video', $video->id, $video->getFileName() ?? '', $thumb, $video->get_link());
        } ?>
        </div>
        <div class="play_video">
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id, 'play', T_('Play'), 'play_album_' . $video->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_video_' . $video->id); ?>
                <?php } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&append=true', 'play_add', T_('Play last'), 'addplay_video_' . $video->id); ?>
            <?php } ?>
        <?php } ?>
        </div>
        <?php
        if (Access::check('interface', 25)) { ?>
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $video->id; ?>_video"><?php echo Rating::show($video->id, 'video'); ?></span>
                <span class="cel_rating" id="userflag_<?php echo $video->id; ?>_video"><?php echo Userflag::show($video->id, 'video'); ?></span>
            <?php } ?>
        <?php } ?>
    </div>
    <?php
    } ?>
<?php
} ?>

<?php Ui::show_box_bottom(); ?>
