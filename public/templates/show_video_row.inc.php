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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;

/** @var Video $libitem */
/** @var Ampache\Repository\Model\Browse $browse */
/** @var bool $hide_genres */
/** @var bool $show_ratings */
/** @var string $cel_cover */
/** @var string $cel_counter */
/** @var string $cel_tags */
/** @var string|null $video_type */

$web_path = AmpConfig::get('web_path');
if (!isset($video_type)) {
    $video_type = ObjectTypeToClassNameMapper::reverseMap(get_class($libitem));
} ?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay')) {
            echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $libitem->id, 'play', T_('Play'), 'play_video_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $libitem->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_video_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_video_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<td class="<?php echo $cel_cover; ?>">
    <?php $art_showed = false;
if ($libitem->get_default_art_kind() == 'preview') {
    $art_showed = Art::display('video', $libitem->id, (string)$libitem->get_fullname(), 9, $libitem->get_link(), false, 'preview');
}
if (!$art_showed) {
    $thumb = ($browse->is_grid_view()) ? 6 : 7;
    Art::display('video', $libitem->id, (string)$libitem->get_fullname(), $thumb, $libitem->get_link());
} ?>
</td>
<td class="cel_title"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
<?php
    echo Ajax::button('?action=basket&type=video&id=' . $libitem->id, 'add', T_('Add to Temporary Playlist'), 'add_' . $libitem->id);
if (Access::check('interface', 25)) { ?>
        <a id="<?php echo 'add_playlist_' . $libitem->id; ?>" onclick="showPlaylistDialog(event, 'video', '<?php echo $libitem->id; ?>')">
            <?php echo Ui::get_icon('playlist_add', T_('Add to playlist')); ?>
        </a>
    <?php } ?>
    </span>
</td>
<?php
if ($video_type != 'video') {
    require Ui::find_template('show_partial_' . $video_type . '_row.inc.php');
} ?>
<td class="cel_release_date"><?php echo $libitem->f_release_date; ?></td>
<td class="cel_codec"><?php echo $libitem->f_codec; ?></td>
<td class="cel_resolution"><?php echo $libitem->f_resolution; ?></td>
<td class="cel_length"><?php echo $libitem->f_length; ?></td>
<?php if (AmpConfig::get('show_played_times')) { ?>
<td class="<?php echo $cel_counter; ?>"><?php echo $libitem->total_count; ?></td>
<?php } ?>
<?php if (!$hide_genres) { ?>
<td class="<?php echo $cel_tags; ?>"><?php echo $libitem->f_tags; ?></td>
<?php } ?>
<?php if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $libitem->id; ?>_video">
                    <?php echo Rating::show($libitem->id, 'video'); ?>
                </span>
                <span class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_video">
                    <?php echo Userflag::show($libitem->id, 'video'); ?>
                </span>
            <?php } ?>
        </td>
    <?php } ?>
<td class="cel_action">
<a href="<?php echo $libitem->get_link(); ?>"><?php echo Ui::get_icon('preferences', T_('Video Information')); ?></a>
<?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) {
    if (AmpConfig::get('sociable')) { ?>
        <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=video&id=<?php echo $libitem->id; ?>"><?php echo Ui::get_icon('comment', T_('Post Shout')); ?></a>
    <?php
    }
}
if (Access::check('interface', 25)) {
    if (AmpConfig::get('share')) {
        echo Share::display_ui('video', $libitem->id, false);
    }
}
if (Access::check_function('download')) { ?>
    <a class="nohtml" href="<?php echo $web_path; ?>/stream.php?action=download&video_id=<?php echo $libitem->id; ?>"><?php echo Ui::get_icon('download', T_('Download')); ?></a>
<?php }
if (Access::check('interface', 50)) { ?>
    <a id="<?php echo 'edit_video_' . $libitem->id; ?>" onclick="showEditDialog('video_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_video_' . $libitem->id; ?>', '<?php echo addslashes(T_('Video Edit')); ?>', 'video_')">
        <?php echo Ui::get_icon('edit', T_('Edit')); ?>
    </a>
<?php
}
if (Catalog::can_remove($libitem)) { ?>
    <a id="<?php echo 'delete_video_' . $libitem->id; ?>" href="<?php echo $web_path; ?> /video.php?action=delete&video_id=<?php echo $libitem->id; ?>">
        <?php echo Ui::get_icon('delete', T_('Delete')); ?>
    </a>
<?php } ?>
</td>
