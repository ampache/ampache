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
 * Make sure preference names are updated to current strings
 */
final class Migration400003 extends AbstractMigration
{
    protected array $changelog = ['Make sure preference names are updated to current strings'];

    public function migrate(): void
    {
        $sql_array = array(
            "UPDATE `preference` SET `preference`.`description` = 'Force HTTP playback regardless of port' WHERE `preference`.`name` = 'force_http_play';",
            "UPDATE `preference` SET `preference`.`description` = 'Playback Type' WHERE `preference`.`name` = 'play_type';",
            "UPDATE `preference` SET `preference`.`description` = 'httpQ Active Instance' WHERE `preference`.`name` = 'httpq_active';",
            "UPDATE `preference` SET `preference`.`description` = 'Now Playing filtered per user' WHERE `preference`.`name` = 'now_playing_per_user';",
            "UPDATE `preference` SET `preference`.`description` = 'Use Subsonic backend' WHERE `preference`.`name` = 'subsonic_backend';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Now Playing information' WHERE `preference`.`name` = 'allow_personal_info_now';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information' WHERE `preference`.`name` = 'allow_personal_info_recent';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming date/time' WHERE `preference`.`name` = 'allow_personal_info_time';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming agent' WHERE `preference`.`name` = 'allow_personal_info_agent';",
            "UPDATE `preference` SET `preference`.`description` = 'Enable URL Rewriting' WHERE `preference`.`name` = 'stream_beautiful_url';",
            "UPDATE `preference` SET `preference`.`description` = 'Destination catalog' WHERE `preference`.`name` = 'upload_catalog';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow user uploads' WHERE `preference`.`name` = 'allow_upload';",
            "UPDATE `preference` SET `preference`.`description` = 'Create a subdirectory per user' WHERE `preference`.`name` = 'upload_subdir';",
            "UPDATE `preference` SET `preference`.`description` = 'Consider the user sender as the track''s artist' WHERE `preference`.`name` = 'upload_user_artist';",
            "UPDATE `preference` SET `preference`.`description` = 'Post-upload script (current directory = upload target directory)' WHERE `preference`.`name` = 'upload_script';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow users to edit uploaded songs' WHERE `preference`.`name` = 'upload_allow_edit';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow users to remove uploaded songs' WHERE `preference`.`name` = 'upload_allow_remove';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Albums of the Moment' WHERE `preference`.`name` = 'home_moment_albums';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Videos of the Moment' WHERE `preference`.`name` = 'home_moment_videos';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Logo' WHERE `preference`.`name` = 'custom_logo';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Login page logo' WHERE `preference`.`name` = 'custom_login_logo';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Favicon' WHERE `preference`.`name` = 'custom_favicon';",
            "UPDATE `preference` SET `preference`.`description` = 'Album - Default sort' WHERE `preference`.`name` = 'album_sort';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Geolocation' WHERE `preference`.`name` = 'Geolocation';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Video Features' WHERE `preference`.`name` = 'allow_video';",
            "UPDATE `preference` SET `preference`.`description` = 'Democratic - Clear votes for expired user sessions' WHERE `preference`.`name` = 'demo_clear_sessions';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Transcoding' WHERE `preference`.`name` = 'transcoding';",
            "UPDATE `preference` SET `preference`.`description` = 'Authorize Flash Web Player' WHERE `preference`.`name` = 'webplayer_flash';",
            "UPDATE `preference` SET `preference`.`description` = 'Authorize HTML5 Web Player' WHERE `preference`.`name` = 'webplayer_html5';",
            "UPDATE `preference` SET `preference`.`description` = 'Web Player browser notifications' WHERE `preference`.`name` = 'browser_notify';",
            "UPDATE `preference` SET `preference`.`description` = 'Web Player browser notifications timeout (seconds)' WHERE `preference`.`name` = 'browser_notify_timeout';",
            "UPDATE `preference` SET `preference`.`description` = 'Authorize JavaScript decoder (Aurora.js) in Web Player' WHERE `preference`.`name` = 'webplayer_aurora';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Now Playing' WHERE `preference`.`name` = 'home_now_playing';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Recently Played' WHERE `preference`.`name` = 'home_recently_played';",
            "UPDATE `preference` SET `preference`.`description` = '# latest episodes to keep' WHERE `preference`.`name` = 'podcast_keep';",
            "UPDATE `preference` SET `preference`.`description` = '# episodes to download when new episodes are available' WHERE `preference`.`name` = 'podcast_new_download';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Transcoding' WHERE `preference`.`name` = 'transcode';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow E-mail notifications' WHERE `preference`.`name` = 'notify_email';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom metadata - Disable these fields' WHERE `preference`.`name` = 'disabled_custom_metadata_fields';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom metadata - Define field list' WHERE `preference`.`name` = 'disabled_custom_metadata_fields_input';",
            "UPDATE `preference` SET `preference`.`description` = 'Auto-pause between tabs' WHERE `preference`.`name` = 'webplayer_pausetabs';"
        );
        foreach ($sql_array as $sql) {
            $this->updateDatabase($sql);
        }
    }
}
