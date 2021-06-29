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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

/** @var Album $libitem */
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
        <?php
            if ($show_direct_play) {
                echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $libitem->get_http_album_query_ids('object_id'), 'play', T_('Play'), 'play_album_' . $libitem->id);
                if (Stream_Playlist::check_autoplay_next()) {
                    echo Ajax::button('?page=stream&action=directplay&object_type=album&object_id=' . $libitem->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_album_' . $libitem->id);
                }
                if (Stream_Playlist::check_autoplay_append()) {
                    echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $libitem->get_http_album_query_ids('object_id') . '&append=true', 'play_add', T_('Play last'), 'addplay_album_' . $libitem->id);
                }
            } ?>
    </div>
</td>
<?php
if (Art::is_enabled()) {
                $name = '[' . $libitem->f_album_artist_name . '] ' . scrub_out($libitem->f_name); ?>
<td class="<?php echo $cel_cover; ?>">
    <?php
    $thumb = (isset($browse) && !$browse->is_grid_view()) ? 11 : 1;
                Art::display('album', $libitem->id, $name, $thumb, AmpConfig::get('web_path') . '/albums.php?action=show&album=' . $libitem->id); ?>
</td>
<?php
            } ?>
<td class="<?php echo $cel_album; ?>"><?php echo $libitem->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php
            if ($show_playlist_add) {
                echo Ajax::button('?action=basket&type=album_full&' . $libitem->get_http_album_query_ids('id'), 'add', T_('Add to Temporary Playlist'), 'add_album_' . $libitem->id);
                echo Ajax::button('?action=basket&type=album_random&' . $libitem->get_http_album_query_ids('id'), 'random', T_('Random to Temporary Playlist'), 'random_album_' . $libitem->id); ?>
        <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, 'album', '<?php if (count($libitem->album_suite) <= 1) {
                    echo $libitem->id;
                } else {
                    echo implode(',', $libitem->album_suite);
                } ?>')">
            <?php echo Ui::get_icon('playlist_add', T_('Add to playlist')); ?>
        </a>
            <?php
            } ?>
    </span>
</td>
<td class="<?php echo $cel_artist; ?>"><?php echo(!empty($libitem->f_album_artist_link) ? $libitem->f_album_artist_link : $libitem->f_artist_link); ?></td>
<td class="cel_songs optional"><?php echo $libitem->song_count; ?></td>
<td class="cel_year"><?php if ($libitem->year > 0) {
                echo $libitem->year;
            } ?></td>
<?php
    if (AmpConfig::get('show_played_times')) { ?>
        <td class="<?php echo $cel_counter; ?> optional"><?php echo $libitem->object_cnt; ?></td>
    <?php
    } ?>
<td class="<?php echo $cel_tags; ?> optional"><?php echo $libitem->f_tags; ?></td>
<?php
    if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $libitem->id; ?>_album">
                    <?php echo Rating::show($libitem->id, 'album'); ?>
                </span>
            <?php
            } ?>

            <?php if (AmpConfig::get('userflags')) { ?>
                <span class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_album">
                    <?php echo Userflag::show($libitem->id, 'album'); ?>
                </span>
            <?php
            } ?>
        </td>
    <?php
    } ?>
<td class="cel_action">
    <?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) {
        if (AmpConfig::get('sociable') && (!$libitem->allow_group_disks || ($libitem->allow_group_disks && count($libitem->album_suite) <= 1))) { ?>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=album&amp;id=<?php echo $libitem->id; ?>">
            <?php echo Ui::get_icon('comment', T_('Post Shout')); ?>
        </a>
    <?php
    }
        if (Access::check('interface', 25)) {
            if (AmpConfig::get('share') && (!$libitem->allow_group_disks || ($libitem->allow_group_disks && count($libitem->album_suite) <= 1))) {
                echo Share::display_ui('album', $libitem->id, false);
            }
        }
        // @todo remove after refactoring
        global $dic;
        $zipHandler = $dic->get(ZipHandlerInterface::class);
        if (Access::check_function('batch_download') && $zipHandler->isZipable('album')) { ?>
            <a class="nohtml" href="<?php echo AmpConfig::get('web_path') ?>/batch.php?action=album&<?php echo $libitem->get_http_album_query_ids('id') ?>">
                <?php echo Ui::get_icon('batch_download', T_('Batch download')); ?>
            </a>
    <?php
    }
        if (Access::check('interface', 50) && (!$libitem->allow_group_disks || ($libitem->allow_group_disks && count($libitem->album_suite) <= 1))) { ?>
            <a id="<?php echo 'edit_album_' . $libitem->id ?>" onclick="showEditDialog('album_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_album_' . $libitem->id ?>', '<?php echo T_('Album Edit') ?>', 'album_')">
                <?php echo Ui::get_icon('edit', T_('Edit')); ?>
            </a>
    <?php
    }
        if (Catalog::can_remove($libitem)) { ?>
            <a id="<?php echo 'delete_album_' . $libitem->id ?>" href="<?php echo AmpConfig::get('web_path') ?>/albums.php?action=delete&album_id=<?php echo $libitem->id ?>">
            <?php echo Ui::get_icon('delete', T_('Delete')); ?>
            </a>
    <?php
    }
    } ?>
</td>
