<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration740001 extends AbstractMigration
{
    protected array $changelog = ['Fix boolean preferences with an incorrect type.'];

    public function migrate(): void
    {
        $this->updateDatabase("UPDATE preference SET `type` = 'boolean' WHERE `type` != 'boolean' AND `name` IN ('access_control','access_list','ajax_load','album_group','album_release_type','allow_democratic_playback','allow_localplay_playback','allow_personal_info_agent','allow_personal_info_now','allow_personal_info_recent','allow_personal_info_time','allow_stream_playback','allow_upload','allow_video','api_always_download','api_enable_3','api_enable_4','api_enable_5','api_enable_6','api_hide_dupe_searches','autoupdate_lastversion_new','autoupdate','bookmark_latest','broadcast_by_default','browse_album_disk_grid_view','browse_album_grid_view','browse_artist_grid_view','browse_filter','browse_live_stream_grid_view','browse_playlist_grid_view','browse_podcast_episode_grid_view','browse_podcast_grid_view','browse_song_grid_view','browse_video_grid_view','browser_notify','catalog_check_duplicate','catalogfav_gridview','catalogfav_compact','condPL','cron_cache','custom_logo_user','daap_backend','demo_clear_sessions','demo_mode','demo_use_search','direct_link','display_menu','download','extended_playlist_links','external_links_bandcamp','external_links_discogs','external_links_duckduckgo','external_links_google','external_links_lastfm','external_links_musicbrainz','external_links_wikipedia','force_http_play','geolocation','hide_genres','hide_single_artist','home_moment_albums','home_moment_videos','home_now_playing','home_recently_played_all','home_recently_played','homedash_newest','homedash_popular','homedash_random','homedash_recent','homedash_trending','index_dashboard_form','libitem_contextmenu','lock_songs','mb_overwrite_name','no_symlinks','notify_email','now_playing_per_user','perpetual_api_session','personalfav_display','quarantine','ratingmatch_flags','ratingmatch_write_tags','rio_global_stats','rio_track_stats','share_social','share','show_album_artist','show_artist','show_donate','show_header_login','show_license','show_lyrics','show_original_year','show_played_times','show_playlist_media_parent','show_playlist_username','show_skipped_times','show_subtitle','show_wrapped','sidebar_hide_browse','sidebar_hide_dashboard','sidebar_hide_information','sidebar_hide_playlist','sidebar_hide_search','sidebar_hide_switcher','sidebar_hide_video','sidebar_light','song_page_title','stream_beautiful_url','subsonic_always_download','subsonic_backend','tadb_overwrite_name','topmenu','ui_fixed','unique_playlist','upload_allow_edit','upload_allow_remove','upload_catalog_pattern','upload_subdir','upload_user_artist','upload','upnp_backend','use_auth','use_original_year','use_play2','webdav_backend','webplayer_aurora','webplayer_confirmclose','webplayer_flash','webplayer_html5','webplayer_pausetabs','xml_rpc');");
    }
}
