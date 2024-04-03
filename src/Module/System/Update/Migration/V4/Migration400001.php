<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\System\Update\Migration\V4;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Make sure people on older databases have the same preference categories
 */
final class Migration400001 extends AbstractMigration
{
    protected array $changelog = [
        'Update preferences for older users to match current subcategory items',
        '(~3.6 introduced subcategories but didn\'t include updates for existing users',
        'This is a cosmetic update and does not affect any operation)',
    ];

    public function migrate(): void
    {
        $sql_array = array(
            "UPDATE `preference` SET `preference`.`subcatagory` = 'library' WHERE `preference`.`name` in ('album_sort', 'show_played_times', 'album_group', 'album_release_type', 'album_release_type_sort', 'libitem_contextmenu', 'browse_filter', 'libitem_browse_alpha') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'backend' WHERE `preference`.`name` in ('subsonic_backend', 'daap_backend', 'daap_pass', 'upnp_backend', 'webdav_backend') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'catalog' WHERE `preference`.`name` = 'catalog_check_duplicate' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'custom' WHERE `preference`.`name` in ('site_title', 'custom_logo', 'custom_login_logo', 'custom_favicon', 'custom_text_footer', 'custom_blankalbum', 'custom_blankmovie') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'feature' WHERE `preference`.`name` in ('download', 'allow_stream_playback', 'allow_democratic_playback', 'share', 'allow_video', 'geolocation') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'home' WHERE `preference`.`name` in ('now_playing_per_user', 'home_moment_albums', 'home_moment_videos', 'home_recently_played', 'home_now_playing') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'httpq' WHERE `preference`.`name` = 'httpq_active' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'lastfm' WHERE `preference`.`name` in ('lastfm_grant_link', 'lastfm_challenge') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'localplay' WHERE `preference`.`name` in ('localplay_controller', 'localplay_level', 'allow_localplay_playback') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'metadata' WHERE `preference`.`name` in ('disabled_custom_metadata_fields', 'disabled_custom_metadata_fields_input') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'mpd' WHERE `preference`.`name` = 'mpd_active' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'notification' WHERE `preference`.`name` in ('browser_notify', 'browser_notify_timeout') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'player' WHERE `preference`.`name` in ('show_lyrics', 'song_page_title', 'webplayer_flash', 'webplayer_html5', 'webplayer_confirmclose', 'webplayer_pausetabs', 'slideshow_time', 'broadcast_by_default', 'direct_play_limit', 'webplayer_aurora') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'podcast' WHERE `preference`.`name` in ('podcast_keep', 'podcast_new_download') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'privacy' WHERE `preference`.`name` in ('allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_personal_info_agent') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'query' WHERE `preference`.`name` in ('popular_threshold', 'offset_limit', 'stats_threshold', 'concerts_limit_future', 'concerts_limit_past') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'share' WHERE `preference`.`name` = 'share_expire' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'shoutcast' WHERE `preference`.`name` = 'shoutcast_active' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'theme' WHERE `preference`.`name` in ('theme_name', 'ui_fixed', 'topmenu', 'theme_color', 'sidebar_light') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'transcoding' WHERE `preference`.`name` in ('transcode_bitrate', 'rate_limit', 'transcode') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'update' WHERE `preference`.`name` in ('autoupdate', 'autoupdate_lastcheck', 'autoupdate_lastversion', 'autoupdate_lastversion_new') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'upload' WHERE `preference`.`name` in ('upload_catalog', 'allow_upload', 'upload_subdir', 'upload_user_artist', 'upload_script', 'upload_allow_edit', 'upload_allow_remove', 'upload_catalog_pattern') AND `preference`.`subcatagory` IS NULL;"
        );
        foreach ($sql_array as $sql) {
            $this->updateDatabase($sql);
        }
    }
}
