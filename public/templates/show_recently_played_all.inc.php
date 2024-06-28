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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Rss\AmpacheRss;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

/** @var array $data */

global $dic;

$ajax_page = $ajax_page ?? 'index';
$user_id   = $user_id ?? -1;
$rss_link  = AmpConfig::get('use_rss') ? '&nbsp' . AmpacheRss::get_display('recently_played', $user_id) : '';
$refresh   = (isset($no_refresh))
    ? ""
    : "&nbsp" . Ajax::button('?page=index&action=refresh_index', 'refresh', T_('Refresh'), 'refresh_index', 'box box_recently_played');
$web_path  = (string)AmpConfig::get('web_path', '');
$is_admin  = Access::check('interface', 100);
$showAlbum = AmpConfig::get('album_group');
UI::show_box_top(T_('Recently Played') . $rss_link . $refresh, 'box_recently_played'); ?>
<table class="tabledata striped-rows">
    <thead>
    <tr class="th-top">
        <th class="cel_play"></th>
        <th class="cel_song"><?php echo T_('Title'); ?></th>
        <th class="cel_add"></th>
        <th class="cel_artist"><?php echo T_('Artist'); ?></th>
        <th class="cel_album"><?php echo T_('Album'); ?></th>
        <th class="cel_year"><?php echo T_('Year'); ?></th>
        <?php if ($user_id > 0) { ?>
            <th class="cel_username"><?php echo T_('Username'); ?></th>
        <?php } ?>
        <th class="cel_lastplayed"><?php echo T_('Last Played'); ?></th>
        <?php if ($is_admin) { ?>
            <th class="cel_agent"><?php echo T_('Agent'); ?></th>
            <th class="cel_delete"></th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $count = 0;
foreach ($data as $row) {
    $row_id    = ($row['user'] > 0) ? (int) $row['user'] : -1;
    $row_user  = new User($row_id);
    $className = ObjectTypeToClassNameMapper::map($row['object_type']);
    /** @var Song|Live_Stream|Podcast_Episode|Video $media */
    $media = new $className($row['object_id']);
    if ($media->isNew()) {
        continue;
    }

    $agent              = ($is_admin) ? $row['agent'] : '';
    $time_string        = '-';
    $has_allowed_recent = (bool) $row['user_recent'];
    $has_allowed_time   = (bool) $row['user_time'];
    $is_allowed_recent  = $is_admin || $user_id == $row_id || $has_allowed_recent;
    $is_allowed_time    = $is_admin || $user_id == $row_id || $has_allowed_time;
    // if you don't allow now_playing don't show the whole row
    if ($is_allowed_recent) {
        // add the time if you've allowed it
        if ($is_allowed_time) {
            $interval = (int) (time() - ($row['date'] ?? 0));

            if ($interval < 60) {
                $time_string = sprintf(nT_('%d second ago', '%d seconds ago', $interval), $interval);
            } elseif ($interval < 3600) {
                $interval    = floor($interval / 60);
                $time_string = sprintf(nT_('%d minute ago', '%d minutes ago', $interval), $interval);
            } elseif ($interval < 86400) {
                $interval    = floor($interval / 3600);
                $time_string = sprintf(nT_('%d hour ago', '%d hours ago', $interval), $interval);
            } elseif ($interval < 604800) {
                $interval    = floor($interval / 86400);
                $time_string = sprintf(nT_('%d day ago', '%d days ago', $interval), $interval);
            } elseif ($interval < 2592000) {
                $interval    = floor($interval / 604800);
                $time_string = sprintf(nT_('%d week ago', '%d weeks ago', $interval), $interval);
            } elseif ($interval < 31556926) {
                $interval    = floor($interval / 2592000);
                $time_string = sprintf(nT_('%d month ago', '%d months ago', $interval), $interval);
            } elseif ($interval < 631138519) {
                $interval    = floor($interval / 31556926);
                $time_string = sprintf(nT_('%d year ago', '%d years ago', $interval), $interval);
            } else {
                $interval    = floor($interval / 315569260);
                $time_string = sprintf(nT_('%d decade ago', '%d decades ago', $interval), $interval);
            }
        }
        $media->format(); ?>
            <tr>
                <td class="cel_play">
                    <span class="cel_play_content">&nbsp;</span>
                    <div class="cel_play_hover">
                        <?php if (AmpConfig::get('directplay')) { ?>
                            <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $media->getId(), 'play', T_('Play'), 'play_song_' . $count . '_' . $media->getId()); ?>
                            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                                <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $media->getId() . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_song_' . $count . '_' . $media->getId()); ?>
                            <?php } ?>
                            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                                <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $media->getId() . '&append=true', 'play_add', T_('Play last'), 'addplay_song_' . $count . '_' . $media->getId()); ?>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </td>
                <td class="cel_song"><?php echo $media->get_f_link(); ?></td>
                <td class="cel_add">
                <span class="cel_item_add">
                    <?php echo Ajax::button('?action=basket&type=' . $row['object_type'] . '&id=' . $media->getId(), 'add', T_('Add to Temporary Playlist'), 'add_' . $count . '_' . $media->getId()); ?>
                    <a id="<?php echo 'add_playlist_' . $count . '_' . $media->getId(); ?>" onclick="showPlaylistDialog(event, 'song', '<?php echo $media->getId(); ?>')">
                        <?php echo Ui::get_icon('playlist_add', T_('Add to playlist')); ?>
                    </a>
                </span>
                </td>
                <td class="cel_artist"><?php echo $media->get_f_artist_link(); ?></td>
                <td class="cel_album"><?php echo ($showAlbum) ? $media->get_f_album_link() : $media->get_f_album_disk_link(); ?></td>
                <td class="cel_year"><?php echo $media->getYear(); ?></td>
                <?php if ($user_id > 0) { ?>
                    <td class="cel_username">
                        <a href="<?php echo $web_path; ?>/stats.php?action=show_user&user_id=<?php echo $row_user->id; ?>">
                            <?php echo scrub_out($row_user->fullname); ?>
                        </a>
                    </td>
                <?php } ?>
                <td class="cel_lastplayed"><?php echo $time_string; ?></td>
                <?php if ($is_admin) { ?>
                    <td class="cel_agent">
                    <?php if (!empty($agent)) {
                        echo Ui::get_icon('info', $agent); ?>
                        </td>
                    <?php
                    } ?>
                    <td class="cel_delete">
                        <?php echo Ajax::button('?page=stats&action=delete_play&activity_id=' . $row['activity_id'], 'delete', T_('Delete'), 'activity_remove_' . $row['activity_id']); ?>
                    </td>
                <?php } ?>
            </tr>
            <?php
                    ++$count;
    }
} ?>
    <?php if (!count($data)) { ?>
        <tr>
            <td colspan="9"><span class="nodata"><?php echo T_('No recently played items found'); ?></span></td>
        </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr class="th-bottom">
        <th class="cel_play"></th>
        <th class="cel_song"><?php echo T_('Title'); ?></th>
        <th class="cel_add"></th>
        <th class="cel_artist"><?php echo T_('Artist'); ?></th>
        <th class="cel_album"><?php echo T_('Album'); ?></th>
        <th class="cel_year"><?php echo T_('Year'); ?></th>
        <?php if ($user_id > 0) { ?>
            <th class="cel_username"><?php echo T_('Username'); ?></th>
        <?php } ?>
        <th class="cel_lastplayed"><?php echo T_('Last Played'); ?></th>
        <?php if ($is_admin) { ?>
            <th class="cel_agent"><?php echo T_('Agent'); ?></th>
            <th class="cel_delete"></th>
        <?php } ?>
    </tr>
    </tfoot>
</table>
<script>
    $(document).ready(function () {
        $("a[rel^='prettyPhoto']").prettyPhoto({
            social_tools: false,
            deeplinking: false
        });
    });
</script>
<?php Ui::show_box_bottom(); ?>
