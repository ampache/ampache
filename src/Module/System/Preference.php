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
 *
 */

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Database\database_object;
use Ampache\Module\Playback\Localplay\LocalPlayTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\CatalogFilterRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;

/**
 * This handles all of the preference stuff for Ampache
 */
class Preference extends database_object
{
    /**
     * The access level the `default` preset gives each preference, as level => the preferences taking it
     *
     * @var array<int, list<string>>
     */
    public const array DEFAULT_LEVELS = [
        AccessLevelEnum::DEFAULT->value => ['libitem_contextmenu', 'show_lyrics', 'theme_color', 'theme_name'],
        AccessLevelEnum::GUEST->value => ['offset_limit', 'playlist_method'],
        AccessLevelEnum::USER->value => ['album_group', 'album_release_type', 'album_release_type_sort', 'album_sort', 'allow_personal_info_agent', 'allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'api_always_download', 'api_enable_3', 'api_enable_4', 'api_enable_5', 'api_enable_6', 'api_enable_8', 'api_force_version', 'api_hidden_playlists', 'api_hide_dupe_searches', 'autoupdate_lastcheck', 'autoupdate_lastversion_new', 'autoupdate_lastversion', 'bookmark_latest', 'broadcast_by_default', 'broadcast_private', 'browse_filter', 'browser_notify_timeout', 'browser_notify', 'custom_datetime', 'custom_logo_user', 'custom_logo', 'custom_timezone', 'demo_clear_sessions', 'direct_play_limit', 'geolocation', 'hide_genres', 'hide_moods', 'hide_single_artist', 'home_moment_albums', 'home_moment_videos', 'home_now_playing', 'home_recently_played_all', 'home_recently_played', 'httpq_active', 'index_dashboard_form', 'jp_volume', 'lastfm_challenge', 'lastfm_grant_link', 'mpd_active', 'notify_email', 'of_the_moment', 'play_type', 'popular_threshold', 'show_album_artist', 'show_artist', 'show_collection', 'show_donate', 'show_folder', 'show_license', 'show_mood', 'show_original_year', 'show_played_times', 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times', 'show_subtitle', 'show_wrapped', 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information', 'sidebar_hide_playlist', 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video', 'sidebar_light', 'sidebar_order_browse', 'sidebar_order_dashboard', 'sidebar_order_information', 'sidebar_order_playlist', 'sidebar_order_search', 'sidebar_order_video', 'slideshow_time', 'song_page_title', 'subsonic_always_download', 'topmenu', 'transcode_bitrate', 'transcode', 'ui_fixed', 'unique_playlist', 'use_original_year', 'webplayer_confirmclose', 'webplayer_pausetabs', 'webplayer_removeplayed', 'subsonic_force_album_artist', 'subsonic_single_user_data'],
        AccessLevelEnum::CONTENT_MANAGER->value => ['now_playing_per_user'],
        AccessLevelEnum::MANAGER->value => ['allow_video', 'custom_blankalbum', 'custom_favicon', 'custom_login_background', 'custom_login_logo', 'custom_text_footer', 'libitem_browse_alpha', 'stats_threshold'],
        AccessLevelEnum::ADMIN->value => ['allow_democratic_playback', 'allow_localplay_playback', 'allow_stream_playback', 'allow_upload', 'autoupdate', 'catalog_check_duplicate', 'cron_cache', 'daap_backend', 'daap_pass', 'demo_use_search', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields', 'download', 'force_http_play', 'lang', 'localplay_controller', 'localplay_level', 'lock_songs', 'perpetual_api_session', 'playlist_type', 'podcast_keep', 'podcast_new_download', 'rate_limit', 'share_expire', 'share', 'show_header_login', 'site_title', 'stream_beautiful_url', 'subsonic_backend', 'upload_access_level', 'upload_allow_edit', 'upload_allow_remove', 'upload_catalog_pattern', 'upload_catalog', 'upload_script', 'upload_subdir', 'upload_user_artist', 'upnp_backend', 'webdav_backend'],
    ];
    /**
     * Every Ampache preference and the row `set_defaults()` writes for it, as
     * name => [value, description, level, type, category, subcategory].
     *
     * Descriptions are plain US-English literals here; `translate_db()` owns the wording and `T_()` is
     * applied at display time.
     *
     * @var array<string, array{0: string, 1: string, 2: int, 3: string, 4: string, 5: ?string}>
     */
    public const array DEFAULTS = [
        'download' => ['1', 'Allow Downloads', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature'],
        'popular_threshold' => ['10', 'Popular Threshold', AccessLevelEnum::USER->value, 'integer', 'interface', 'query'],
        'transcode_bitrate' => ['128000', 'Transcode bitrate - Default', AccessLevelEnum::USER->value, 'integer', 'streaming', 'transcoding'],
        'site_title' => ['Ampache :: For the Love of Music', 'Website Title', AccessLevelEnum::ADMIN->value, 'string', 'interface', 'custom'],
        'lock_songs' => ['0', 'Lock Songs', AccessLevelEnum::ADMIN->value, 'boolean', 'system', null],
        'force_http_play' => ['0', 'Force HTTP playback regardless of port', AccessLevelEnum::ADMIN->value, 'boolean', 'system', null],
        'play_type' => ['web_player', 'Playback Type', AccessLevelEnum::USER->value, 'special', 'streaming', null],
        'lang' => ['en_US', 'Language', AccessLevelEnum::ADMIN->value, 'special', 'interface', null],
        'playlist_type' => ['m3u', 'Playlist Type', AccessLevelEnum::ADMIN->value, 'special', 'playlist', null],
        'theme_name' => ['reborn', 'Theme', AccessLevelEnum::DEFAULT->value, 'special', 'interface', 'theme'],
        'localplay_level' => ['0', 'Localplay Access', AccessLevelEnum::ADMIN->value, 'special', 'options', 'localplay'],
        'localplay_controller' => ['0', 'Localplay Type', AccessLevelEnum::ADMIN->value, 'special', 'options', 'localplay'],
        'allow_stream_playback' => ['1', 'Allow Streaming', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature'],
        'allow_democratic_playback' => ['0', 'Allow Democratic Play', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature'],
        'allow_localplay_playback' => ['0', 'Allow Localplay Play', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'localplay'],
        'stats_threshold' => ['7', 'Statistics Day Threshold', AccessLevelEnum::USER->value, 'integer', 'interface', 'query'],
        'offset_limit' => ['50', 'Offset Limit', AccessLevelEnum::DEFAULT->value, 'integer', 'interface', 'query'],
        'rate_limit' => ['8192', 'Download Rate Limit', AccessLevelEnum::ADMIN->value, 'integer', 'streaming', 'transcoding'],
        'playlist_method' => ['default', 'Playlist Method', AccessLevelEnum::DEFAULT->value, 'string', 'playlist', null],
        'transcode' => ['default', 'Allow Transcoding', AccessLevelEnum::USER->value, 'string', 'streaming', 'transcoding'],
        'show_lyrics' => ['0', 'Show lyrics', AccessLevelEnum::DEFAULT->value, 'boolean', 'interface', 'player'],
        'lastfm_grant_link' => ['', 'Last.FM Grant URL', AccessLevelEnum::USER->value, 'string', 'plugins', 'last.fm'],
        'lastfm_challenge' => ['', 'Last.FM Submit Challenge', AccessLevelEnum::USER->value, 'string', 'internal', 'last.fm'],
        'now_playing_per_user' => ['1', 'Now Playing filtered per user', AccessLevelEnum::CONTENT_MANAGER->value, 'boolean', 'interface', 'home'],
        'album_sort' => ['default', 'Album - Default sort', AccessLevelEnum::USER->value, 'string', 'interface', 'library'],
        'show_played_times' => ['0', 'Show # played', AccessLevelEnum::USER->value, 'string', 'interface', 'browse'],
        'song_page_title' => ['1', 'Show current song in Web player page title', AccessLevelEnum::USER->value, 'boolean', 'interface', 'player'],
        'subsonic_backend' => ['1', 'Use Subsonic backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend'],
        'allow_personal_info_now' => ['1', 'Share Now Playing information', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy'],
        'allow_personal_info_recent' => ['1', 'Share Recently Played information', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy'],
        'allow_personal_info_time' => ['1', 'Share Recently Played information - Allow access to streaming date/time', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy'],
        'allow_personal_info_agent' => ['1', 'Share Recently Played information - Allow access to streaming agent', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy'],
        'ui_fixed' => ['0', 'Fix header position on compatible themes', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme'],
        'autoupdate' => ['1', 'Check for Ampache updates automatically', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'update'],
        'autoupdate_lastcheck' => ['', 'AutoUpdate last check time', AccessLevelEnum::USER->value, 'string', 'internal', 'update'],
        'autoupdate_lastversion' => ['', 'AutoUpdate last version from last check', AccessLevelEnum::USER->value, 'string', 'internal', 'update'],
        'autoupdate_lastversion_new' => ['', 'AutoUpdate last version from last check is newer', AccessLevelEnum::USER->value, 'boolean', 'internal', 'update'],
        'webplayer_confirmclose' => ['0', 'Confirmation when closing current playing window', AccessLevelEnum::USER->value, 'boolean', 'interface', 'player'],
        'webplayer_pausetabs' => ['1', 'Auto-pause between tabs', AccessLevelEnum::USER->value, 'boolean', 'interface', 'player'],
        'stream_beautiful_url' => ['0', 'Enable URL Rewriting', AccessLevelEnum::ADMIN->value, 'boolean', 'streaming', null],
        'share' => ['0', 'Allow Share', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature'],
        'share_expire' => ['7', 'Share links default expiration days (0=never)', AccessLevelEnum::ADMIN->value, 'integer', 'system', 'share'],
        'slideshow_time' => ['0', 'Artist slideshow inactivity time', AccessLevelEnum::USER->value, 'integer', 'interface', 'player'],
        'broadcast_by_default' => ['0', 'Broadcast web player by default', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player'],
        'broadcast_private' => ['1', 'Require a session to listen to my broadcasts', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player'],
        'album_group' => ['1', 'Album - Group multiple disks', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'topmenu' => ['0', 'Top menu', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme'],
        'demo_clear_sessions' => ['0', 'Democratic - Clear votes for expired user sessions', AccessLevelEnum::USER->value, 'boolean', 'playlist', null],
        'show_donate' => ['1', 'Show donate button in footer', AccessLevelEnum::USER->value, 'boolean', 'interface', null],
        'upload_catalog' => ['-1', 'Destination catalog', AccessLevelEnum::ADMIN->value, 'integer', 'options', 'upload'],
        'allow_upload' => ['0', 'Allow user uploads', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload'],
        'upload_subdir' => ['1', 'Create a subdirectory per user', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload'],
        'upload_user_artist' => ['0', 'Consider the user sender as the track\'s artist', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload'],
        'upload_script' => ['', 'Post-upload script (current directory = upload target directory)', AccessLevelEnum::ADMIN->value, 'string', 'system', 'upload'],
        'upload_allow_edit' => ['1', 'Allow users to edit uploaded songs', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload'],
        'daap_backend' => ['0', 'Use DAAP backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend'],
        'daap_pass' => ['', 'DAAP backend password', AccessLevelEnum::ADMIN->value, 'string', 'system', 'backend'],
        'upnp_backend' => ['0', 'Use UPnP backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend'],
        'allow_video' => ['0', 'Allow Video Features', AccessLevelEnum::MANAGER->value, 'integer', 'options', 'feature'],
        'album_release_type' => ['1', 'Album - Group per release type', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'direct_play_limit' => ['500', 'Limit direct play to maximum media count', AccessLevelEnum::USER->value, 'integer', 'interface', 'player'],
        'home_moment_albums' => ['1', 'Show Albums of the Moment', AccessLevelEnum::USER->value, 'integer', 'interface', 'home'],
        'home_moment_videos' => ['0', 'Show Videos of the Moment', AccessLevelEnum::USER->value, 'integer', 'interface', 'home'],
        'home_recently_played' => ['1', 'Show Recently Played', AccessLevelEnum::USER->value, 'integer', 'interface', 'home'],
        'home_now_playing' => ['1', 'Show Now Playing', AccessLevelEnum::USER->value, 'integer', 'interface', 'home'],
        'custom_logo' => ['', 'Custom URL - Logo', AccessLevelEnum::USER->value, 'string', 'interface', 'custom'],
        'album_release_type_sort' => ['album,ep,live,single', 'Album - Group per release type sort', AccessLevelEnum::USER->value, 'string', 'interface', 'library'],
        'browser_notify' => ['1', 'Web Player browser notifications', AccessLevelEnum::USER->value, 'integer', 'interface', 'notification'],
        'browser_notify_timeout' => ['10', 'Web Player browser notifications timeout (seconds)', AccessLevelEnum::USER->value, 'integer', 'interface', 'notification'],
        'geolocation' => ['0', 'Allow Geolocation', AccessLevelEnum::USER->value, 'integer', 'options', 'feature'],
        'upload_allow_remove' => ['1', 'Allow users to remove uploaded songs', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload'],
        'custom_login_logo' => ['', 'Custom URL - Login page logo', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface'],
        'custom_favicon' => ['', 'Custom URL - Favicon', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface'],
        'custom_text_footer' => ['', 'Custom text footer', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface'],
        'webdav_backend' => ['0', 'Use WebDAV backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend'],
        'notify_email' => ['0', 'Allow E-mail notifications', AccessLevelEnum::USER->value, 'boolean', 'options', null],
        'theme_color' => ['dark', 'Theme color', AccessLevelEnum::DEFAULT->value, 'special', 'interface', 'theme'],
        'disabled_custom_metadata_fields' => ['', 'Custom metadata - Disable these fields', AccessLevelEnum::ADMIN->value, 'string', 'system', 'metadata'],
        'disabled_custom_metadata_fields_input' => ['', 'Custom metadata - Additional fields to disable', AccessLevelEnum::ADMIN->value, 'string', 'system', 'metadata'],
        'podcast_keep' => ['0', '# latest episodes to keep', AccessLevelEnum::ADMIN->value, 'integer', 'system', 'podcast'],
        'podcast_new_download' => ['0', '# episodes to download when new episodes are available', AccessLevelEnum::ADMIN->value, 'integer', 'system', 'podcast'],
        'libitem_contextmenu' => ['1', 'Library item context menu', AccessLevelEnum::DEFAULT->value, 'boolean', 'interface', 'library'],
        'upload_catalog_pattern' => ['0', 'Rename uploaded file according to catalog pattern', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload'],
        'catalog_check_duplicate' => ['0', 'Check library item at import time and disable duplicates', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'catalog'],
        'browse_filter' => ['1', 'Show filter box on browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'sidebar_light' => ['0', 'Light sidebar by default', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'custom_blankalbum' => ['', 'Custom blank album default image', AccessLevelEnum::MANAGER->value, 'string', 'interface', 'custom'],
        'libitem_browse_alpha' => ['', 'Alphabet browsing by default for following library items (album,artist,...)', AccessLevelEnum::MANAGER->value, 'string', 'interface', 'browse'],
        'show_skipped_times' => ['0', 'Show # skipped', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'custom_datetime' => ['', 'Custom datetime', AccessLevelEnum::USER->value, 'string', 'interface', 'custom'],
        'cron_cache' => ['0', 'Cache computed SQL data (eg. media hits stats) using a cron', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'catalog'],
        'unique_playlist' => ['0', 'Only add unique items to playlists', AccessLevelEnum::USER->value, 'boolean', 'playlist', null],
        'of_the_moment' => ['6', 'Set the amount of items Album/Video of the Moment will display', AccessLevelEnum::USER->value, 'integer', 'interface', 'home'],
        'custom_login_background' => ['', 'Custom URL - Login page background', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface'],
        'show_license' => ['1', 'Show License', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'use_original_year' => ['0', 'Browse by Original Year for albums (falls back to Year)', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'hide_single_artist' => ['0', 'Hide the Song Artist column for Albums with one Artist', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'hide_genres' => ['0', 'Hide the Genre column in browse table rows', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'hide_moods' => ['1', 'Hide the Mood column in browse table rows', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'subsonic_always_download' => ['0', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'api_enable_3' => ['1', 'Allow Ampache API3 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'api_enable_4' => ['1', 'Allow Ampache API4 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'api_enable_5' => ['1', 'Allow Ampache API5 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'api_force_version' => ['0', 'Force a specific API response no matter what version you send', AccessLevelEnum::USER->value, 'special', 'options', 'api'],
        'show_playlist_username' => ['1', 'Show playlist owner username in titles', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'api_hidden_playlists' => ['', 'Hide playlists in Subsonic and API clients that start with this string', AccessLevelEnum::USER->value, 'string', 'options', 'api'],
        'api_hide_dupe_searches' => ['0', 'Hide smartlists that match playlist names in Subsonic and API clients', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'show_album_artist' => ['1', 'Show \'Album Artists\' link in the main sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'show_artist' => ['0', 'Show \'Artists\' link in the main sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'demo_use_search' => ['0', 'Democratic - Use smartlists for base playlist', AccessLevelEnum::ADMIN->value, 'boolean', 'system', null],
        'webplayer_removeplayed' => ['0', 'Remove tracks before the current playlist item in the webplayer when played', AccessLevelEnum::USER->value, 'special', 'streaming', 'player'],
        'api_enable_6' => ['1', 'Allow Ampache API6 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'upload_access_level' => ['25', 'Upload Access Level', AccessLevelEnum::ADMIN->value, 'special', 'system', 'upload'],
        'show_subtitle' => ['1', 'Show Album subtitle on links (if available)', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'show_original_year' => ['1', 'Show Album original year on links (if available)', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse'],
        'show_header_login' => ['1', 'Show the login / registration links in the site header', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'interface'],
        'custom_timezone' => ['', 'Custom timezone (Override PHP date.timezone)', AccessLevelEnum::USER->value, 'string', 'interface', 'custom'],
        'bookmark_latest' => ['0', 'Only keep the latest media bookmark', AccessLevelEnum::USER->value, 'boolean', 'options', null],
        'jp_volume' => ['0.8', 'Default webplayer volume', AccessLevelEnum::USER->value, 'special', 'streaming', 'player'],
        'perpetual_api_session' => ['0', 'API sessions do not expire', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend'],
        'home_recently_played_all' => ['1', 'Show all media types in Recently Played', AccessLevelEnum::USER->value, 'bool', 'interface', 'home'],
        'show_wrapped' => ['1', 'Enable access to your personal "Spotify Wrapped" from your user page', AccessLevelEnum::USER->value, 'bool', 'interface', 'privacy'],
        'mini_player' => ['0', 'Lock this user into the mini player interface', AccessLevelEnum::ADMIN->value, 'boolean', 'interface', 'theme'],
        'sidebar_hide_switcher' => ['0', 'Hide sidebar switcher arrows', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'sidebar_hide_browse' => ['0', 'Hide the Browse menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'sidebar_hide_dashboard' => ['0', 'Hide the Dashboard menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'sidebar_hide_video' => ['0', 'Hide the Video menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'sidebar_hide_search' => ['0', 'Hide the Search menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'sidebar_hide_playlist' => ['0', 'Hide the Playlist menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'sidebar_hide_information' => ['0', 'Hide the Information menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'custom_logo_user' => ['0', 'Custom URL - Use your avatar for header logo', AccessLevelEnum::USER->value, 'boolean', 'interface', 'custom'],
        'index_dashboard_form' => ['0', 'Use Dashboard links for the index page header', AccessLevelEnum::USER->value, 'boolean', 'interface', 'home'],
        'sidebar_order_browse' => ['10', 'Custom CSS Order - Browse', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar'],
        'sidebar_order_dashboard' => ['15', 'Custom CSS Order - Dashboard', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar'],
        'sidebar_order_video' => ['20', 'Custom CSS Order - Video', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar'],
        'sidebar_order_playlist' => ['30', 'Custom CSS Order - Playlist', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar'],
        'sidebar_order_search' => ['40', 'Custom CSS Order - Search', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar'],
        'sidebar_order_information' => ['60', 'Custom CSS Order - Information', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar'],
        'api_always_download' => ['0', 'Force API streams to download. (Enable scrobble in your client to record stats)', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'external_links_google' => ['1', 'Show Google search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'external_links_duckduckgo' => ['1', 'Show DuckDuckGo search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'external_links_wikipedia' => ['1', 'Show Wikipedia search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'external_links_lastfm' => ['1', 'Show Last.fm search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'external_links_bandcamp' => ['1', 'Show Bandcamp search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'external_links_musicbrainz' => ['1', 'Show MusicBrainz search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'extended_playlist_links' => ['0', 'Show extended links for playlist media', AccessLevelEnum::USER->value, 'boolean', 'playlist', null],
        'external_links_discogs' => ['1', 'Show Discogs search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library'],
        'browse_song_grid_view' => ['0', 'Force Grid View on Song browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_album_grid_view' => ['0', 'Force Grid View on Album browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_album_disk_grid_view' => ['0', 'Force Grid View on Album Disk browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_artist_grid_view' => ['0', 'Force Grid View on Artist browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_live_stream_grid_view' => ['0', 'Force Grid View on Radio Station browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_playlist_grid_view' => ['0', 'Force Grid View on Playlist browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_video_grid_view' => ['0', 'Force Grid View on Video browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_podcast_grid_view' => ['0', 'Force Grid View on Podcast browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'browse_podcast_episode_grid_view' => ['0', 'Force Grid View on Podcast Episode browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies'],
        'show_playlist_media_parent' => ['0', 'Show Artist column on playlist media rows', AccessLevelEnum::USER->value, 'boolean', 'playlist', null],
        'subsonic_legacy' => ['0', 'Enable legacy Subsonic API responses for compatibility issues', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'subsonic_force_album_artist' => ['0', 'Force Album Artist for Subsonic API responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'subsonic_single_user_data' => ['1', 'Use single user data for Subsonic API responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'api_enable_8' => ['1', 'Allow Ampache API8 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'api'],
        'show_folder' => ['1', 'Show \'Folders\' link in the main sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'show_collection' => ['1', 'Show \'Collections\' link in the main sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'show_mood' => ['1', 'Show \'Moods\' link in the main sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar'],
        'encode_target' => ['', 'Transcode output format - Audio Default', AccessLevelEnum::USER->value, 'transcoding', 'streaming', 'transcoding'],
        'encode_video_target' => ['', 'Transcode output format - Video Default', AccessLevelEnum::USER->value, 'transcoding', 'streaming', 'transcoding'],
        'encode_player_webplayer_target' => ['', 'Transcode output format - Web Player (overrides default)', AccessLevelEnum::USER->value, 'transcoding', 'streaming', 'transcoding'],
        'encode_player_api_target' => ['', 'Transcode output format - API (overrides default)', AccessLevelEnum::USER->value, 'transcoding', 'streaming', 'transcoding'],
        'max_bit_rate' => ['0', 'Maximum transcode bitrate for dynamic downsampling in bps (0 = disabled)', AccessLevelEnum::USER->value, 'integer', 'streaming', 'transcoding'],
        'min_bit_rate' => ['8000', 'Minimum transcode bitrate for dynamic downsampling in bps', AccessLevelEnum::USER->value, 'integer', 'streaming', 'transcoding'],
        'transcode_bitrate_webplayer' => ['0', 'Transcode bitrate - Web Player (overrides default)', AccessLevelEnum::USER->value, 'integer', 'streaming', 'transcoding'],
        'transcode_bitrate_api' => ['0', 'Transcode bitrate - API (overrides default)', AccessLevelEnum::USER->value, 'integer', 'streaming', 'transcoding'],
        'cron_cache_live_count' => ['0', 'Add live plays to the cached count for accurate stats (Require: Cron Cache)', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'catalog'],
        'httpq_active' => ['0', 'HTTPQ Active Instance', AccessLevelEnum::USER->value, 'integer', 'internal', 'httpq'],
    ];
    /**
     * plugin and module preferences might not be there but they need to be kept if you're using them
     */
    public const array PLUGIN_LIST = [
        'amazon_base_url',
        'amazon_developer_associate_tag',
        'amazon_developer_private_api_key',
        'amazon_developer_public_key',
        'amazon_max_results_pages',
        'bitly_api_key',
        'bitly_username',
        'catalogfav_gridview',
        'catalogfav_max_items',
        'catalogfav_compact',
        'catalogfav_order',
        'discogs_api_key',
        'discogs_secret_api_key',
        'flickr_api_key',
        'ftl_max_items',
        'ftl_order',
        'gmaps_api_key',
        'googleanalytics_tracking_id',
        'headphones_api_key',
        'headphones_api_url',
        'homedash_max_items',
        'homedash_newest',
        'homedash_order',
        'homedash_popular',
        'homedash_random',
        'homedash_recent',
        'homedash_trending',
        'httpq_active',
        'index_dashboard_form',
        'lastfm_challenge',
        'lastfm_grant_link',
        'librefm_challenge',
        'librefm_grant_link',
        'listenbrainz_token',
        'matomo_site_id',
        'matomo_url',
        'mb_overwrite_name',
        'mpd_active',
        'paypal_business',
        'paypal_currency_code',
        'personalfav_display',
        'personalfav_order',
        'personalfav_playlist',
        'personalfav_smartlist',
        'piwik_site_id',
        'piwik_url',
        'ratingmatch_flag_rule',
        'ratingmatch_flags',
        'ratingmatch_star1_rule',
        'ratingmatch_star2_rule',
        'ratingmatch_star3_rule',
        'ratingmatch_star4_rule',
        'ratingmatch_star5_rule',
        'ratingmatch_stars',
        'ratingmatch_write_tags',
        'rssview_feed_url',
        'rssview_max_items',
        'rssview_order',
        'shouthome_max_items',
        'shouthome_order',
        'stream_control_bandwidth_days',
        'stream_control_bandwidth_max',
        'stream_control_hits_days',
        'stream_control_hits_max',
        'stream_control_time_days',
        'stream_control_time_max',
        'tadb_api_key',
        'tadb_overwrite_name',
        'upnp_active',
        'vlc_active',
        'xbmc_active',
        'yourls_api_key',
        'yourls_domain',
        'yourls_use_idn',
    ];

    /**
     * The value every preset writes onto a user, as preset => value => the preferences taking it.
     *
     * A name appears once per preset; two clauses claiming the same one would leave the winner up to
     * statement order.
     *
     * A numeric-looking value becomes an int key, which is why the repository casts it back on the way
     * into the statement.
     *
     * @var array<string, array<int|string, list<string>>>
     */
    public const array PRESETS = [
        'default' => [
            '-1' => ['upload_catalog'],
            '' => ['api_hidden_playlists', 'autoupdate_lastcheck', 'autoupdate_lastversion_new', 'autoupdate_lastversion', 'custom_blankalbum', 'custom_datetime', 'custom_favicon', 'custom_login_background', 'custom_login_logo', 'custom_logo', 'custom_text_footer', 'custom_timezone', 'daap_pass', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields', 'lastfm_challenge', 'lastfm_grant_link', 'libitem_browse_alpha', 'upload_script'],
            '0.8' => ['jp_volume'],
            '0' => ['album_sort', 'allow_upload', 'allow_video', 'api_force_version', 'api_hide_dupe_searches', 'bookmark_latest', 'broadcast_by_default', 'catalog_check_duplicate', 'cron_cache', 'custom_logo_user', 'daap_backend', 'demo_clear_sessions', 'demo_use_search', 'direct_play_limit', 'force_http_play', 'geolocation', 'hide_genres', 'hide_single_artist', 'home_moment_videos', 'httpq_active', 'index_dashboard_form', 'lock_songs', 'mpd_active', 'notify_email', 'perpetual_api_session', 'share', 'show_album_artist', 'show_lyrics', 'show_played_times', 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times', 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information', 'sidebar_hide_playlist', 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video', 'sidebar_light', 'slideshow_time', 'stream_beautiful_url', 'subsonic_always_download', 'topmenu', 'ui_fixed', 'unique_playlist', 'upload_catalog_pattern', 'upload_user_artist', 'upnp_backend', 'use_original_year', 'webdav_backend', 'webplayer_confirmclose', 'webplayer_removeplayed', 'api_always_download'],
            '1' => ['album_group', 'album_release_type', 'broadcast_private', 'browse_filter', 'allow_democratic_playback', 'allow_localplay_playback', 'allow_personal_info_agent', 'allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_stream_playback', 'api_enable_3', 'api_enable_4', 'api_enable_5', 'api_enable_6', 'autoupdate', 'browser_notify', 'download', 'hide_moods', 'home_moment_albums', 'home_now_playing', 'home_recently_played_all', 'home_recently_played', 'libitem_contextmenu', 'now_playing_per_user', 'podcast_new_download', 'show_artist', 'show_collection', 'show_donate', 'show_folder', 'show_header_login', 'show_license', 'show_mood', 'show_original_year', 'show_subtitle', 'show_wrapped', 'song_page_title', 'subsonic_backend', 'upload_allow_edit', 'upload_allow_remove', 'upload_subdir', 'webplayer_pausetabs'],
            '10' => ['browser_notify_timeout', 'podcast_keep', 'popular_threshold', 'sidebar_order_browse'],
            '100' => ['localplay_level'],
            '15' => ['sidebar_order_dashboard'],
            '20' => ['sidebar_order_video'],
            '25' => ['upload_access_level'],
            '30' => ['sidebar_order_playlist'],
            '32' => ['transcode_bitrate'],
            '40' => ['sidebar_order_search'],
            '50' => ['offset_limit'],
            '6' => ['of_the_moment'],
            '60' => ['sidebar_order_information'],
            '7' => ['share_expire', 'stats_threshold'],
            '8192' => ['rate_limit'],
            'album,ep,live,single' => ['album_release_type_sort'],
            'Ampache :: For the Love of Music' => ['site_title'],
            'dark' => ['theme_color'],
            'default' => ['playlist_method', 'transcode'],
            'en_US' => ['lang'],
            'm3u' => ['playlist_type'],
            'mpd' => ['localplay_controller'],
            'reborn' => ['theme_name'],
            'web_player' => ['play_type'],
        ],
        'minimalist' => [
            '-1' => ['upload_catalog'],
            '' => ['api_hidden_playlists', 'autoupdate_lastcheck', 'autoupdate_lastversion_new', 'autoupdate_lastversion', 'custom_blankalbum', 'custom_datetime', 'custom_favicon', 'custom_login_background', 'custom_login_logo', 'custom_logo', 'custom_text_footer', 'custom_timezone', 'daap_pass', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields', 'lastfm_challenge', 'lastfm_grant_link', 'libitem_browse_alpha', 'upload_script'],
            '0.8' => ['jp_volume'],
            '0' => ['album_sort', 'allow_upload', 'allow_video', 'api_force_version', 'api_hide_dupe_searches', 'bookmark_latest', 'broadcast_by_default', 'browse_filter', 'catalog_check_duplicate', 'cron_cache', 'custom_logo_user', 'daap_backend', 'demo_clear_sessions', 'demo_use_search', 'direct_play_limit', 'download', 'force_http_play', 'geolocation', 'hide_genres', 'hide_single_artist', 'home_moment_videos', 'httpq_active', 'index_dashboard_form', 'lock_songs', 'mpd_active', 'notify_email', 'perpetual_api_session', 'share', 'show_album_artist', 'show_lyrics', 'show_played_times', 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times', 'show_wrapped', 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information', 'sidebar_hide_playlist', 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video', 'sidebar_light', 'slideshow_time', 'stream_beautiful_url', 'subsonic_always_download', 'topmenu', 'ui_fixed', 'unique_playlist', 'upload_catalog_pattern', 'upload_user_artist', 'upnp_backend', 'use_original_year', 'webdav_backend', 'webplayer_confirmclose', 'webplayer_removeplayed', 'api_always_download'],
            '1' => ['album_group', 'album_release_type', 'broadcast_private', 'allow_democratic_playback', 'allow_localplay_playback', 'allow_personal_info_agent', 'allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_stream_playback', 'api_enable_3', 'api_enable_4', 'api_enable_5', 'api_enable_6', 'autoupdate', 'browser_notify', 'hide_moods', 'home_moment_albums', 'home_now_playing', 'home_recently_played_all', 'home_recently_played', 'libitem_contextmenu', 'now_playing_per_user', 'podcast_new_download', 'show_artist', 'show_collection', 'show_donate', 'show_folder', 'show_header_login', 'show_license', 'show_mood', 'show_original_year', 'show_subtitle', 'song_page_title', 'subsonic_backend', 'upload_allow_edit', 'upload_allow_remove', 'upload_subdir', 'webplayer_pausetabs'],
            '10' => ['browser_notify_timeout', 'podcast_keep', 'popular_threshold', 'sidebar_order_browse'],
            '100' => ['localplay_level'],
            '15' => ['sidebar_order_dashboard'],
            '20' => ['sidebar_order_video'],
            '25' => ['upload_access_level'],
            '30' => ['sidebar_order_playlist'],
            '32' => ['transcode_bitrate'],
            '40' => ['sidebar_order_search'],
            '50' => ['offset_limit'],
            '6' => ['of_the_moment'],
            '60' => ['sidebar_order_information'],
            '7' => ['share_expire', 'stats_threshold'],
            '8192' => ['rate_limit'],
            'album,ep,live,single' => ['album_release_type_sort'],
            'Ampache :: For the Love of Music' => ['site_title'],
            'dark' => ['theme_color'],
            'default' => ['playlist_method', 'transcode'],
            'en_US' => ['lang'],
            'm3u' => ['playlist_type'],
            'mpd' => ['localplay_controller'],
            'reborn' => ['theme_name'],
            'web_player' => ['play_type'],
        ],
        'community' => [
            '-1' => ['upload_catalog'],
            '' => ['api_hidden_playlists', 'autoupdate_lastcheck', 'autoupdate_lastversion_new', 'autoupdate_lastversion', 'custom_blankalbum', 'custom_datetime', 'custom_favicon', 'custom_login_background', 'custom_login_logo', 'custom_logo', 'custom_text_footer', 'custom_timezone', 'daap_pass', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields', 'lastfm_challenge', 'lastfm_grant_link', 'libitem_browse_alpha', 'upload_script'],
            '0.8' => ['jp_volume'],
            '0' => ['album_sort', 'allow_upload', 'allow_video', 'api_force_version', 'api_hide_dupe_searches', 'bookmark_latest', 'broadcast_by_default', 'browse_filter', 'catalog_check_duplicate', 'cron_cache', 'custom_logo_user', 'daap_backend', 'demo_clear_sessions', 'demo_use_search', 'direct_play_limit', 'download', 'force_http_play', 'geolocation', 'hide_genres', 'hide_single_artist', 'home_moment_videos', 'home_now_playing', 'home_recently_played_all', 'home_recently_played', 'httpq_active', 'index_dashboard_form', 'lock_songs', 'mpd_active', 'notify_email', 'perpetual_api_session', 'show_album_artist', 'show_lyrics', 'show_played_times', 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times', 'show_wrapped', 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information', 'sidebar_hide_playlist', 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video', 'sidebar_light', 'slideshow_time', 'stream_beautiful_url', 'subsonic_always_download', 'topmenu', 'ui_fixed', 'unique_playlist', 'upload_catalog_pattern', 'upload_user_artist', 'upnp_backend', 'use_original_year', 'webdav_backend', 'webplayer_confirmclose', 'webplayer_removeplayed', 'api_always_download'],
            '1' => ['album_group', 'album_release_type', 'broadcast_private', 'allow_democratic_playback', 'allow_localplay_playback', 'allow_personal_info_agent', 'allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_stream_playback', 'api_enable_3', 'api_enable_4', 'api_enable_5', 'api_enable_6', 'autoupdate', 'browser_notify', 'hide_moods', 'home_moment_albums', 'libitem_contextmenu', 'now_playing_per_user', 'podcast_new_download', 'share', 'show_artist', 'show_collection', 'show_donate', 'show_folder', 'show_header_login', 'show_license', 'show_mood', 'show_original_year', 'show_subtitle', 'song_page_title', 'subsonic_backend', 'upload_allow_edit', 'upload_allow_remove', 'upload_subdir', 'webplayer_pausetabs'],
            '10' => ['browser_notify_timeout', 'podcast_keep', 'popular_threshold', 'sidebar_order_browse'],
            '100' => ['localplay_level'],
            '15' => ['sidebar_order_dashboard'],
            '20' => ['sidebar_order_video'],
            '25' => ['upload_access_level'],
            '30' => ['sidebar_order_playlist'],
            '32' => ['transcode_bitrate'],
            '40' => ['sidebar_order_search'],
            '50' => ['offset_limit'],
            '6' => ['of_the_moment'],
            '60' => ['sidebar_order_information'],
            '7' => ['share_expire', 'stats_threshold'],
            '8192' => ['rate_limit'],
            'album,ep,live,single' => ['album_release_type_sort'],
            'Ampache :: For the Love of Music' => ['site_title'],
            'dark' => ['theme_color'],
            'default' => ['playlist_method', 'transcode'],
            'en_US' => ['lang'],
            'm3u' => ['playlist_type'],
            'mpd' => ['localplay_controller'],
            'reborn' => ['theme_name'],
            'web_player' => ['play_type'],
        ],
    ];
    /**
     * This array contains System preferences that can (should) not be edited or deleted from the api
     */
    public const array SYSTEM_LIST = [
        'album_group',
        'album_release_type_sort',
        'album_release_type',
        'album_sort',
        'allow_democratic_playback',
        'allow_localplay_playback',
        'allow_personal_info_agent',
        'allow_personal_info_now',
        'allow_personal_info_recent',
        'allow_personal_info_time',
        'allow_stream_playback',
        'allow_upload',
        'allow_video',
        'api_always_download',
        'api_enable_3',
        'api_enable_4',
        'api_enable_5',
        'api_enable_6',
        'api_enable_8',
        'api_force_version',
        'api_hidden_playlists',
        'api_hide_dupe_searches',
        'autoupdate_lastcheck',
        'autoupdate_lastversion_new',
        'autoupdate_lastversion',
        'autoupdate',
        'bookmark_latest',
        'broadcast_by_default',
        'broadcast_private',
        'browse_album_disk_grid_view',
        'browse_album_grid_view',
        'browse_artist_grid_view',
        'browse_filter',
        'browse_live_stream_grid_view',
        'browse_playlist_grid_view',
        'browse_podcast_episode_grid_view',
        'browse_podcast_grid_view',
        'browse_song_grid_view',
        'browse_video_grid_view',
        'browser_notify_timeout',
        'browser_notify',
        'catalog_check_duplicate',
        'cron_cache',
        'cron_cache_live_count',
        'custom_blankalbum',
        'custom_datetime',
        'custom_favicon',
        'custom_login_background',
        'custom_login_logo',
        'custom_logo_user',
        'custom_logo',
        'custom_text_footer',
        'custom_timezone',
        'daap_backend',
        'daap_pass',
        'demo_clear_sessions',
        'demo_use_search',
        'direct_play_limit',
        'disabled_custom_metadata_fields_input',
        'disabled_custom_metadata_fields',
        'download',
        'encode_player_api_target',
        'encode_player_webplayer_target',
        'encode_target',
        'encode_video_target',
        'extended_playlist_links',
        'external_links_bandcamp',
        'external_links_discogs',
        'external_links_duckduckgo',
        'external_links_google',
        'external_links_lastfm',
        'external_links_musicbrainz',
        'external_links_wikipedia',
        'force_http_play',
        'geolocation',
        'hide_genres',
        'hide_moods',
        'hide_single_artist',
        'home_moment_albums',
        'home_moment_videos',
        'home_now_playing',
        'home_recently_played_all',
        'home_recently_played',
        'httpq_active',
        'index_dashboard_form',
        'jp_volume',
        'lang',
        'lastfm_challenge',
        'lastfm_grant_link',
        'libitem_browse_alpha',
        'libitem_contextmenu',
        'localplay_controller',
        'localplay_level',
        'lock_songs',
        'max_bit_rate',
        'min_bit_rate',
        'mini_player',
        'notify_email',
        'now_playing_per_user',
        'of_the_moment',
        'offset_limit',
        'perpetual_api_session',
        'play_type',
        'playlist_method',
        'playlist_type',
        'podcast_keep',
        'podcast_new_download',
        'popular_threshold',
        'rate_limit',
        'share_expire',
        'share',
        'show_album_artist',
        'show_artist',
        'show_collection',
        'show_donate',
        'show_folder',
        'show_header_login',
        'show_license',
        'show_lyrics',
        'show_mood',
        'show_original_year',
        'show_played_times',
        'show_playlist_media_parent',
        'show_playlist_username',
        'show_skipped_times',
        'show_subtitle',
        'show_wrapped',
        'sidebar_hide_browse',
        'sidebar_hide_dashboard',
        'sidebar_hide_information',
        'sidebar_hide_playlist',
        'sidebar_hide_search',
        'sidebar_hide_switcher',
        'sidebar_hide_video',
        'sidebar_light',
        'sidebar_order_browse',
        'sidebar_order_dashboard',
        'sidebar_order_information',
        'sidebar_order_playlist',
        'sidebar_order_search',
        'sidebar_order_video',
        'site_title',
        'slideshow_time',
        'song_page_title',
        'stats_threshold',
        'stream_beautiful_url',
        'subsonic_always_download',
        'subsonic_backend',
        'subsonic_force_album_artist',
        'subsonic_legacy',
        'subsonic_single_user_data',
        'theme_color',
        'theme_name',
        'topmenu',
        'transcode_bitrate_api',
        'transcode_bitrate_webplayer',
        'transcode_bitrate',
        'transcode',
        'ui_fixed',
        'unique_playlist',
        'upload_access_level',
        'upload_allow_edit',
        'upload_allow_remove',
        'upload_catalog_pattern',
        'upload_catalog',
        'upload_script',
        'upload_subdir',
        'upload_user_artist',
        'upnp_backend',
        'use_original_year',
        'webdav_backend',
        'webplayer_confirmclose',
        'webplayer_pausetabs',
        'webplayer_removeplayed',
    ];
    protected const string DB_TABLENAME = 'preference';

    /**
     * __constructor
     * This does nothing... amazing isn't it!
     */
    private function __construct() {}

    /**
     * clean_preferences
     * This removes any garbage
     */
    public static function clean_preferences(): void
    {
        // First remove garbage
        self::getPreferenceRepository()->collectGarbage();
    }

    /**
     * clear_from_session
     * This clears the users preferences, this is done whenever modifications are made to the preferences
     * or the admin resets something
     */
    public static function clear_from_session(): void
    {
        if (
            isset($_SESSION)
            && array_key_exists('userdata', $_SESSION)
            && array_key_exists('preferences', $_SESSION['userdata'])
        ) {
            unset($_SESSION['userdata']['preferences']);
        }
    }

    /**
     * delete
     * This deletes the specified preference, a name or an ID can be passed
     */
    public static function delete(int|string $preference): bool
    {
        if (self::exists($preference) === 0) {
            return true;
        }

        if (self::getPreferenceRepository()->deleteByNameOrId($preference)) {
            self::clean_preferences();

            return true;
        }

        return false;
    }

    /**
     * exists
     * This just checks to see if a preference currently exists
     */
    public static function exists(int|string $preference): int
    {
        // Don't assume it's the name
        return self::getPreferenceRepository()->countByNameOrId($preference);
    }

    /**
     * fix_preferences
     * This takes the preferences, explodes what needs to
     * become an array and boolean everything. Nothing to do with fix_user_preferences(), which repairs rows.
     * @param array<string, mixed> $results
     */
    public static function fix_preferences(array $results): array
    {
        $arrays = [
            'allow_zip_types',
            'art_order',
            'auth_methods',
            'getid3_tag_order',
            'metadata_order_video',
            'metadata_order',
            'registration_display_fields',
            'registration_mandatory_fields',
            'wanted_types',
        ];

        foreach ($arrays as $item) {
            $results[$item] = (array_key_exists($item, $results) && trim((string) $results[$item]))
                ? explode(',', (string) $results[$item])
                : [];
        }

        foreach ($results as $key => $data) {
            if (!is_array($data)) {
                if (strcasecmp((string) $data, "true") == "0") {
                    $results[$key] = 1;
                }

                if (strcasecmp((string) $data, "false") == "0") {
                    $results[$key] = 0;
                }
            }
        }

        return $results;
    }

    /**
     * fix_user_preferences
     * Removes duplicate rows for one user and inserts whatever they are missing, seeded from the system user.
     * Pass -1 to repair the system user itself. Not to be confused with fix_preferences(), which coerces values.
     */
    public static function fix_user_preferences(int $user_id): void
    {
        $repository       = self::getPreferenceRepository();
        $filterRepository = self::getCatalogFilterRepository();

        // Check default group (autoincrement starts at 1 so force it to be 0)
        if ($filterRepository->repairDefaultGroup()) {
            debug_event(self::class, 'fix_preferences restore DEFAULT catalog_filter_group', 2);
        }

        // Make sure the language a user has is valid
        $repository->repairLanguagePreferences();

        // Make sure all current catalogs are in the default group map
        $filterRepository->addMissingCatalogsToDefaultGroup();

        /* Get All Preferences for the current user */
        $results      = [];
        $zero_results = [];

        foreach ($repository->getStoredPreferences($user_id) as $row) {
            $pref_id = $row['preference'];
            // Check for duplicates
            if (isset($results[$pref_id])) {
                $repository->deleteDuplicatePreference($user_id, $pref_id, $row['value']);
            } else {
                // if its set
                $results[$pref_id] = 1;
            }
        }

        // If your user is missing preferences we copy the value from system (Except for plugins and system prefs)
        if ($user_id != -1) {
            foreach ($repository->getSystemDefaultPreferences() as $row) {
                $zero_results[$row['preference']] = [
                    'name' => $row['name'],
                    'value' => $row['value']
                ];
            }
        } // if not user -1

        // get me _EVERYTHING_, minus the system-only rows when this is a real user
        foreach ($repository->getAllPreferences($user_id == -1) as $row) {
            $key = $row['id'];

            // Check if this preference is set
            if (!isset($results[$key])) {
                if (isset($zero_results[$key])) {
                    $row['value'] = $zero_results[$key]['value'];
                    $row['name']  = $zero_results[$key]['name'];
                }

                $repository->addUserPreference($user_id, $key, $row['name'], $row['value']);
            }
        } // while preferences
    }

    /**
     * get
     * This returns a nice flat array of all of the possible preferences for the specified user
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     level: int,
     *     description: string,
     *     value: mixed,
     *     type: string,
     *     category: string,
     *     subcategory: ?string
     * }>
     */
    public static function get(string $pref_name, int $user_id): array
    {
        $row = self::getPreferenceRepository()->getUserPreferenceRow($pref_name, $user_id, $user_id != -1);
        if ($row === []) {
            return [];
        }

        return [[
            'id' => $row['id'],
            'name' => $row['name'],
            'level' => $row['level'],
            'description' => $row['description'],
            'value' => $row['value'],
            'type' => $row['type'],
            'category' => $row['category'],
            'subcategory' => $row['subcategory'],
        ]];
    }

    /**
     * get_by_user
     * Return a preference for specific user identifier
     * Get all preference the first time and add them to the cache
     * @see User::getPreferenceValue()
     */
    public static function get_by_user(int $user_id, string $pref_name): int|string|null
    {
        //debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '}...', 5);
        if (parent::is_cached('get_by_user-' . $pref_name, $user_id)) {
            return (parent::get_from_cache('get_by_user-' . $pref_name, $user_id)[0]);
        }

        $column_name = 'name'; // Ampache 7
        $repository  = self::getPreferenceRepository();
        $keyedByName = $repository->hasUserPreferenceName();
        if (!$keyedByName) {
            $column_name = 'preference'; // Backward compatibility for Ampache < 7
            $pref_name   = self::id_from_name($pref_name);
        }
        //debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '} -- no cache, need to do one', 5);

        // Get default preferences from user -1
        $pref_default = $repository->getUserValues(-1, $keyedByName);

        // Get user specific preferences
        $pref_user = $repository->getUserValues($user_id, $keyedByName);

        // Merge them (override default with user-specific preference)
        $pref = array_replace($pref_default, $pref_user);

        // Now cache all of them
        foreach ($pref as $key => $value) {
            parent::add_to_cache('get_by_user-' . $key, $user_id, [$value]);
        }

        // Handle if a parameter is missing
        if (
            empty($pref_name)
            || !array_key_exists($pref_name, $pref)
        ) {
            debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '} -- this preference is missing, return default value', 5);

            return '';
        }

        return $pref[$pref_name];
    }

    /**
     * get_categories
     * This returns an array of the names of the different possible sections
     * it ignores the 'internal' category
     * @return string[]
     */
    public static function get_categories(): array
    {
        $results = [];
        foreach (self::getPreferenceRepository()->getCategories() as $category) {
            if ($category != 'internal') {
                $results[] = $category;
            }
        }

        return $results;
    }

    /**
     * get_special_values
     * This returns an array of the values for special preferences which are not kept in the database
     * @return array<int|string>|null
     */
    public static function get_special_values(string $name, User $user): ?array
    {
        switch ($name) {
            case 'upload_catalog':
                return $user->get_catalogs('music');
            case 'playlist_type':
                return [
                    'simple_m3u',
                    'pls',
                    'asx',
                    'ram',
                    'xspf',
                    'm3u'
                ];
            case 'lang':
                return array_keys(get_languages());
            case 'localplay_controller':
                return array_keys(LocalPlayTypeEnum::TYPE_MAPPING);
            case 'api_force_version':
                return [
                    0,
                    3,
                    4,
                    5,
                    6
                ];
            case 'ratingmatch_stars':
                return [
                    '0',
                    '1',
                    '2',
                    '3',
                    '4',
                    '5',
                ];
            case 'localplay_level':
            case 'upload_access_level':
                return [
                    '0',
                    '5',
                    '25',
                    '50',
                    '75',
                    '100',
                ];
            case 'webplayer_removeplayed':
                return [
                    '0',
                    '1',
                    '2',
                    '3',
                    '5',
                    '10',
                    '999',
                ];
            case 'transcode':
                return [
                    'never',
                    'default',
                    'always',
                ];
            case 'album_sort':
                return [
                    'default',
                    'year_asc',
                    'year_desc',
                    'name_asc',
                    'name_desc',
                ];
            case 'encode_target':
            case 'encode_player_webplayer_target':
            case 'encode_player_api_target':
                return array_merge([''], Stream::get_available_encode_formats('audio'));
            case 'encode_video_target':
                return array_merge([''], Stream::get_available_encode_formats('video'));
        }

        return null;
    }

    /**
     * has_access
     * This checks to see if the current user has access to modify this preference
     * as defined by the preference name
     */
    public static function has_access(string $preference): bool
    {
        // Nothing for those demo thugs
        if (AmpConfig::get('demo_mode')) {
            return false;
        }

        $level = self::getPreferenceRepository()->getLevel($preference);

        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::from((int) $level));
    }

    /**
     * id_from_name
     * This takes a name and returns the id
     */
    public static function id_from_name(string $name): ?int
    {
        if (parent::is_cached('id_from_name', $name)) {
            return (int) (parent::get_from_cache('id_from_name', $name))[0];
        }

        $preferenceId = self::getPreferenceRepository()->findIdByName($name);
        if ($preferenceId !== null) {
            parent::add_to_cache('id_from_name', $name, [$preferenceId]);

            return $preferenceId;
        }

        return null;
    }

    /**
     * init
     * This grabs the preferences and then loads them into conf it should be run on page load
     * to initialize the needed variables
     */
    public static function init(): bool
    {
        $user    = Core::get_global('user');
        $user_id = $user->id ?? -1;

        // First go ahead and try to load it from the preferences
        if (self::load_from_session($user_id)) {
            return true;
        }

        /* Get Global Preferences */
        $results = [];
        $types   = [];
        foreach (self::getPreferenceRepository()->getInitRows($user_id) as $row) {
            $value          = $row['system_value'] ?? $row['value'];
            $name           = $row['name'];
            $results[$name] = $value;
            $types[$name]   = (string) ($row['type'] ?? 'string');
        }

        /* Set the Theme mojo */
        if (array_key_exists('theme_name', $results) && strlen((string) $results['theme_name']) > 0) {
            // In case the theme was removed
            if (!Core::is_readable(__DIR__ . '/../../../themes/' . $results['theme_name'])) {
                unset($results['theme_name']);
            }
        } else {
            unset($results['theme_name']);
        }

        // Default theme if we don't get anything from their
        // preferences because we're going to want at least something otherwise
        // the page is going to be really ugly
        if (empty($results['theme_name'])) {
            $results['theme_name'] = 'reborn';
        }

        $results['theme_path'] = '/themes/' . $results['theme_name'];

        // Load theme settings
        $theme_cfg                 = get_theme($results['theme_name']);
        $results['theme_css_base'] = $theme_cfg['base'] ?? null;

        // Default theme color fallback
        if (!isset($results['theme_color'])) {
            $results['theme_color'] = 'dark';
        }

        if (strlen((string) $results['theme_color']) > 0) {
            // In case the color was removed
            if (!Core::is_readable(__DIR__ . '/../../../themes/' . $results['theme_name'] . '/templates/' . $results['theme_color'] . '.css')) {
                unset($results['theme_color']);
            }
        } else {
            unset($results['theme_color']);
        }

        if (!isset($results['theme_color'])) {
            $results['theme_color'] = (isset($theme_cfg['colors']))
                ? strtolower((string) $theme_cfg['colors'][0])
                : 'dark';
        }

        // A preference with no value carries no information -- it is the seeded default, not a choice -- so it
        // must not replace a value the config file set. `encode_target` ships as "mp3" yet seeds an empty row,
        // which blanked it and left the transcoder with no target at all.
        foreach ($results as $name => $value) {
            if (
                ($value === null || $value === '')
                && !in_array(AmpConfig::get((string) $name), [null, ''], true)
            ) {
                unset($results[$name]);
            }
        }

        foreach ($results as $name => $value) {
            $results[$name] = match ($types[$name] ?? 'string') {
                'boolean' => make_bool($value),
                'integer' => (int) $value,
                default => $value,
            };
        }

        AmpConfig::set_by_array($results, true);
        $_SESSION['userdata']['preferences'] = $results;
        $_SESSION['userdata']['uid']         = $user_id;

        return true;
    }

    /**
     * insert
     * This inserts a new preference into the preference table
     * it does NOT sync up the users, that should be done independently
     */
    public static function insert(
        string $name,
        string $description,
        float|int|string $default,
        int $level,
        string $type,
        string $category,
        ?string $subcategory = null,
        bool $replace = false,
    ): bool {
        if ($replace) {
            self::delete($name);
        }

        if (!$replace && self::exists($name)) {
            return true;
        }

        if ($subcategory !== null) {
            $subcategory = strtolower($subcategory);
        }

        if (!self::getPreferenceRepository()->insertPreference($name, $description, $default, $level, $type, $category, $subcategory)) {
            return false;
        }

        debug_event(self::class, 'Inserted preference: ' . $name, 3);

        // clear current user preferences
        self::clear_from_session();

        return true;
    }

    /**
     * is_boolean
     * This returns true / false if the preference in question is a boolean preference
     * This is currently only used by the debug view, could be used other places.. wouldn't be a half
     * bad idea
     */
    public static function is_boolean(string $key): bool
    {
        $boolean_array = [
            'access_control',
            'access_list',
            'admin_enable_required',
            'admin_notify_reg',
            'album_art_store_disk',
            'album_group',
            'album_release_type',
            'allow_democratic_playback',
            'allow_localplay_playback',
            'allow_personal_info_agent',
            'allow_personal_info_now',
            'allow_personal_info_recent',
            'allow_personal_info_time',
            'allow_php_themes',
            'allow_public_registration',
            'allow_stream_playback',
            'allow_upload_scripts',
            'allow_upload',
            'allow_video',
            'allow_zip_download',
            'api_always_download',
            'api_enable_3',
            'api_enable_4',
            'api_enable_5',
            'api_enable_6',
            'api_hide_dupe_searches',
            'art_zip_add',
            'auth_password_save',
            'auto_create',
            'autoupdate_lastversion_new',
            'autoupdate',
            'bookmark_latest',
            'broadcast_by_default',
            'broadcast_private',
            'broadcast',
            'browse_album_disk_grid_view',
            'browse_album_grid_view',
            'browse_artist_grid_view',
            'browse_filter',
            'browse_live_stream_grid_view',
            'browse_playlist_grid_view',
            'browse_podcast_episode_grid_view',
            'browse_podcast_grid_view',
            'browse_song_grid_view',
            'browse_video_grid_view',
            'browser_notify',
            'cache_aif',
            'cache_aiff',
            'cache_ape',
            'cache_flac',
            'cache_m4a',
            'cache_mp3',
            'cache_mpc',
            'cache_oga',
            'cache_ogg',
            'cache_opus',
            'cache_remote',
            'cache_shn',
            'cache_wav',
            'cache_wma',
            'captcha_public_reg',
            'catalog_check_duplicate',
            'catalog_disable',
            'catalog_filter',
            'catalog_verify_by_album',
            'catalog_verify_by_time',
            'catalogfav_compact',
            'catalogfav_gridview',
            'composer_no_dev',
            'condPL',
            'cookie_disclaimer',
            'cookie_secure',
            'cron_cache',
            'cron_cache_live_count',
            'custom_logo_user',
            'daap_backend',
            'debug',
            'deferred_ext_metadata',
            'delete_from_disk',
            'demo_clear_sessions',
            'demo_mode',
            'demo_use_search',
            'direct_link',
            'directplay',
            'disable_xframe_sameorigin',
            'display_menu',
            'download',
            'downsample_remote',
            'enable_custom_metadata',
            'extended_playlist_links',
            'external_auto_update',
            'external_links_bandcamp',
            'external_links_discogs',
            'external_links_duckduckgo',
            'external_links_google',
            'external_links_lastfm',
            'external_links_musicbrainz',
            'external_links_wikipedia',
            'force_http_play',
            'force_ssl',
            'gather_song_art',
            'generate_video_preview',
            'geolocation',
            'getid3_detect_id3v2_encoding',
            'hide_ampache_messages',
            'hide_genres',
            'hide_moods',
            'hide_search',
            'hide_single_artist',
            'home_moment_albums',
            'home_moment_videos',
            'home_now_playing',
            'home_recently_played_all',
            'home_recently_played',
            'homedash_max_items',
            'homedash_newest',
            'homedash_popular',
            'homedash_random',
            'homedash_recent',
            'homedash_trending',
            'index_dashboard_form',
            'label',
            'ldap_start_tls',
            'libitem_contextmenu',
            'licensing',
            'live_stream',
            'lock_songs',
            'mail_auth',
            'mail_enable',
            'mb_overwrite_name',
            'memory_cache',
            'mini_player',
            'no_symlinks',
            'notify_email',
            'now_playing_per_user',
            'oidc_auto_redirect',
            'oidc_disable_ssl_verify',
            'oidc_use_userinfo',
            'perpetual_api_session',
            'personalfav_display',
            'playlist_art',
            'podcast',
            'prevent_multiple_logins',
            'quarantine',
            'rating_browse_filter',
            'rating_browse_minimum_stars',
            'ratingmatch_flags',
            'ratingmatch_write_tags',
            'ratings',
            'require_localnet_session',
            'require_session',
            'resize_images',
            'rio_global_stats',
            'rio_track_stats',
            'send_full_stream',
            'session_cookiesecure',
            'share_social',
            'share',
            'show_album_artist',
            'show_artist',
            'show_donate',
            'show_footer_statistics',
            'show_header_login',
            'show_license',
            'show_lyrics',
            'show_mood',
            'show_original_year',
            'show_played_times',
            'show_playlist_media_parent',
            'show_playlist_username',
            'show_similar',
            'show_skipped_times',
            'show_song_art',
            'show_subtitle',
            'show_wrapped',
            'sidebar_hide_browse',
            'sidebar_hide_dashboard',
            'sidebar_hide_information',
            'sidebar_hide_playlist',
            'sidebar_hide_search',
            'sidebar_hide_switcher',
            'sidebar_hide_video',
            'sidebar_light',
            'simple_user_mode',
            'sociable',
            'song_page_title',
            'statistical_graphs',
            'stream_beautiful_url',
            'subsonic_always_download',
            'subsonic_backend',
            'subsonic_force_album_artist',
            'subsonic_legacy',
            'subsonic_single_user_data',
            'tadb_overwrite_name',
            'topmenu',
            'track_user_ip',
            'transcode_player_customize',
            'ui_fixed',
            'unique_playlist',
            'upload_allow_edit',
            'upload_allow_remove',
            'upload_catalog_pattern',
            'upload_script',
            'upload_subdir',
            'upload_user_artist',
            'upload',
            'upnp_backend',
            'use_auth',
            'use_now_playing_embedded',
            'use_original_year',
            'use_rss',
            'user_agreement',
            'user_create_streamtoken',
            'user_no_email_confirm',
            'vite_dev',
            'wanted_auto_accept',
            'wanted',
            'waveform',
            'webdav_backend',
            'webplayer_confirmclose',
            'webplayer_debug',
            'webplayer_pausetabs',
            'write_tags',
            'xml_rpc',
        ];

        return in_array($key, $boolean_array);
    }

    /**
     * load_from_session
     * This loads the preferences from the session rather then creating a connection to the database
     */
    public static function load_from_session(int $uid = -1): bool
    {
        if (!isset($_SESSION)) {
            return false;
        }

        if (
            array_key_exists('userdata', $_SESSION)
            && array_key_exists('preferences', $_SESSION['userdata'])
            && is_array($_SESSION['userdata']['preferences'])
            && $_SESSION['userdata']['uid'] == $uid
        ) {
            AmpConfig::set_by_array($_SESSION['userdata']['preferences'], true);

            return true;
        }

        return false;
    }

    /**
     * name_from_id
     * This returns the name from an id, it's the exact opposite
     * of the function above it, amazing!
     */
    public static function name_from_id(int|string $pref_id): ?string
    {
        return self::getPreferenceRepository()->findNameById($pref_id);
    }

    /**
     * rebuild_all_preferences
     * This rebuilds the user preferences for all installed users, called by the plugin functions
     */
    public static function rebuild_all_preferences(): void
    {
        $repository = self::getPreferenceRepository();

        // Garbage collection, then drop the system prefs that leaked onto users and resync the stored names
        $repository->collectPreferenceGarbage();

        // Fix the system user preferences first
        self::fix_user_preferences(-1);

        // only repair users holding fewer preferences than exist, which matters on a large user database
        foreach ($repository->getIdsMissingPreferences() as $missing_user_id) {
            self::fix_user_preferences($missing_user_id);
        }
    }

    /**
     * rename
     * This renames a preference in the database
     */
    public static function rename(string $old, string $new): void
    {
        self::getPreferenceRepository()->rename($old, $new);
    }

    /**
     * set_defaults
     * Make sure the default prefs are set! (taken from the default DB file `resources/sql/ampache.sql`)
     */
    public static function set_defaults(): void
    {
        $repository = self::getPreferenceRepository();
        foreach ($repository->findMissingNames(self::SYSTEM_LIST) as $name) {
            $row = self::DEFAULTS[$name] ?? null;
            if ($row === null) {
                debug_event(self::class, 'ERROR: missing preference insert code for: ' . $name, 1);

                continue;
            }

            debug_event(self::class, 'Insert preference: ' . $name, 2);
            if (!$repository->insertDefault($name, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5])) {
                debug_event(self::class, 'ERROR: could not insert preference: ' . $name, 1);
            }
        }

        // Ensure valid prefs are set
        self::rebuild_all_preferences();
    }

    /**
     * set_level
     * Set access level to change preferences, useful for locked down sites and for resetting to the default values
     */
    public static function set_level(string $level = 'default'): bool
    {
        $repository = self::getPreferenceRepository();

        $blanket = match ($level) {
            'guest' => AccessLevelEnum::GUEST,
            'user' => AccessLevelEnum::USER,
            'content_manager' => AccessLevelEnum::CONTENT_MANAGER,
            'manager' => AccessLevelEnum::MANAGER,
            'admin' => AccessLevelEnum::ADMIN,
            default => null,
        };

        if ($blanket instanceof AccessLevelEnum) {
            return $repository->setAllLevels($blanket->value);
        }

        if ($level !== 'default') {
            return false;
        }

        return $repository->setLevels(self::DEFAULT_LEVELS);
    }

    /**
     * set_preset
     * Set user preferences to configured preset values ('system', 'default', 'minimalist', 'community')
     */
    public static function set_preset(string $username, string $preset): bool
    {
        $user = User::get_from_username($username);
        if ($user === null) {
            return false;
        }

        debug_event(self::class, 'Apply preference preset ' . $preset . ' to: ' . $username, 3);

        $repository = self::getPreferenceRepository();
        if ($preset === 'system') {
            // take whatever the server currently has
            return $repository->copySystemPreferences($user->getId());
        }

        $values = self::PRESETS[$preset] ?? null;
        if ($values === null) {
            return false;
        }

        return $repository->setUserPreferenceValues($user->getId(), $values);
    }

    /**
     * translate_db
     * Make sure the default prefs are readable by the users
     */
    public static function translate_db(): void
    {
        $sql        = "UPDATE `preference` SET `preference`.`description` = ? WHERE `preference`.`name` = ? AND `preference`.`description` != ?;";
        $pref_array = [
            'album_group' => 'Album - Group multiple disks',
            'album_release_type_sort' => 'Album - Group per release type sort',
            'album_release_type' => 'Album - Group per release type',
            'album_sort' => 'Album - Default sort',
            'allow_democratic_playback' => 'Allow Democratic Play',
            'allow_localplay_playback' => 'Allow Localplay Play',
            'allow_personal_info_agent' => 'Share Recently Played information - Allow access to streaming agent',
            'allow_personal_info_now' => 'Share Now Playing information',
            'allow_personal_info_recent' => 'Share Recently Played information',
            'allow_personal_info_time' => 'Share Recently Played information - Allow access to streaming date/time',
            'allow_stream_playback' => 'Allow Streaming',
            'allow_upload' => 'Allow user uploads',
            'allow_video' => 'Allow Video Features',
            'amazon_base_url' => 'Amazon base url',
            'amazon_developer_associate_tag' => 'Amazon associate tag',
            'amazon_developer_private_api_key' => 'Amazon Secret Access Key',
            'amazon_developer_public_key' => 'Amazon Access Key ID',
            'amazon_max_results_pages' => 'Amazon max results pages',
            'api_always_download' => 'Force API streams to download. (Enable scrobble in your client to record stats)',
            'api_enable_3' => 'Allow Ampache API3 responses',
            'api_enable_4' => 'Allow Ampache API4 responses',
            'api_enable_5' => 'Allow Ampache API5 responses',
            'api_enable_6' => 'Allow Ampache API6 responses',
            'api_enable_8' => 'Allow Ampache API8 responses',
            'api_force_version' => 'Force a specific API response no matter what version you send',
            'api_hidden_playlists' => 'Hide playlists in Subsonic and API clients that start with this string',
            'api_hide_dupe_searches' => 'Hide smartlists that match playlist names in Subsonic and API clients',
            'autoupdate_lastcheck' => 'AutoUpdate last check time',
            'autoupdate_lastversion_new' => 'AutoUpdate last version from last check is newer',
            'autoupdate_lastversion' => 'AutoUpdate last version from last check',
            'autoupdate' => 'Check for Ampache updates automatically',
            'bitly_api_key' => 'Bit.ly API key',
            'bitly_username' => 'Bit.ly Username',
            'bookmark_latest' => 'Only keep the latest media bookmark',
            'broadcast_by_default' => 'Broadcast web player by default',
            'broadcast_private' => 'Require a session to listen to my broadcasts',
            'browse_album_disk_grid_view' => 'Force Grid View on Album Disk browse',
            'browse_album_grid_view' => 'Force Grid View on Album browse',
            'browse_artist_grid_view' => 'Force Grid View on Artist browse',
            'browse_filter' => 'Show filter box on browse',
            'browse_live_stream_grid_view' => 'Force Grid View on Radio Station browse',
            'browse_playlist_grid_view' => 'Force Grid View on Playlist browse',
            'browse_podcast_episode_grid_view' => 'Force Grid View on Podcast Episode browse',
            'browse_podcast_grid_view' => 'Force Grid View on Podcast browse',
            'browse_song_grid_view' => 'Force Grid View on Song browse',
            'browse_video_grid_view' => 'Force Grid View on Video browse',
            'browser_notify_timeout' => 'Web Player browser notifications timeout (seconds)',
            'browser_notify' => 'Web Player browser notifications',
            'catalog_check_duplicate' => 'Check library item at import time and disable duplicates',
            'catalogfav_gridview' => 'Catalog favorites grid view display',
            'catalogfav_max_items' => 'Catalog favorites max items',
            'catalogfav_compact' => 'Catalog favorites media row display',
            'catalogfav_order' => 'Plugin CSS order',
            'cron_cache' => 'Cache computed SQL data (eg. media hits stats) using a cron',
            'cron_cache_live_count' => 'Add live plays to the cached count for accurate stats (Require: Cron Cache)',
            'custom_blankalbum' => 'Custom blank album default image',
            'custom_datetime' => 'Custom datetime',
            'custom_favicon' => 'Custom URL - Favicon',
            'custom_login_background' => 'Custom URL - Login page background',
            'custom_login_logo' => 'Custom URL - Login page logo',
            'custom_logo' => 'Custom URL - Logo',
            'custom_logo_user' => 'Custom URL - Use your avatar for header logo',
            'custom_text_footer' => 'Custom text footer',
            'custom_timezone' => 'Custom timezone (Override PHP date.timezone)',
            'daap_backend' => 'Use DAAP backend',
            'daap_pass' => 'DAAP backend password',
            'demo_clear_sessions' => 'Democratic - Clear votes for expired user sessions',
            'demo_use_search' => 'Democratic - Use smartlists for base playlist',
            'direct_play_limit' => 'Limit direct play to maximum media count',
            'disabled_custom_metadata_fields_input' => 'Custom metadata - Additional fields to disable',
            'disabled_custom_metadata_fields' => 'Custom metadata - Disable these fields',
            'discogs_api_key' => 'Discogs consumer key',
            'discogs_secret_api_key' => 'Discogs secret',
            'download' => 'Allow Downloads',
            'encode_target' => 'Transcode output format - Audio Default',
            'encode_video_target' => 'Transcode output format - Video Default',
            'encode_player_webplayer_target' => 'Transcode output format - Web Player (overrides default)',
            'encode_player_api_target' => 'Transcode output format - API (overrides default)',
            'extended_playlist_links' => 'Show extended links for playlist media',
            'external_links_google' => 'Show Google search icon on library items',
            'external_links_discogs' => 'Show Discogs search icon on library items',
            'external_links_duckduckgo' => 'Show DuckDuckGo search icon on library items',
            'external_links_wikipedia' => 'Show Wikipedia search icon on library items',
            'external_links_lastfm' => 'Show Last.fm search icon on library items',
            'external_links_bandcamp' => 'Show Bandcamp search icon on library items',
            'external_links_musicbrainz' => 'Show MusicBrainz search icon on library items',
            'flickr_api_key' => 'Flickr API key',
            'force_http_play' => 'Force HTTP playback regardless of port',
            'ftl_max_items' => 'Friends timeline max items',
            'ftl_order' => 'Plugin CSS order',
            'geolocation' => 'Allow Geolocation',
            'gmaps_api_key' => 'Google Maps API key',
            'googleanalytics_tracking_id' => 'Google Analytics Tracking ID',
            'headphones_api_key' => 'Headphones API key',
            'headphones_api_url' => 'Headphones URL',
            'hide_genres' => 'Hide the Genre column in browse table rows',
            'hide_moods' => 'Hide the Mood column in browse table rows',
            'hide_single_artist' => 'Hide the Song Artist column for Albums with one Artist',
            'home_moment_albums' => 'Show Albums of the Moment',
            'home_moment_videos' => 'Show Videos of the Moment',
            'home_now_playing' => 'Show Now Playing',
            'home_recently_played' => 'Show Recently Played',
            'home_recently_played_all' => 'Show all media types in Recently Played',
            'homedash_max_items' => 'Home Dashboard max items',
            'homedash_random' => 'Random',
            'homedash_newest' => 'Newest',
            'homedash_recent' => 'Recent',
            'homedash_trending' => 'Trending',
            'homedash_popular' => 'Popular',
            'homedash_order' => 'Plugin CSS order',
            'httpq_active' => 'HTTPQ Active Instance',
            'index_dashboard_form' => 'Use Dashboard links for the index page header',
            'jp_volume' => 'Default webplayer volume',
            'lang' => 'Language',
            'lastfm_challenge' => 'Last.FM Submit Challenge',
            'lastfm_grant_link' => 'Last.FM Grant URL',
            'libitem_browse_alpha' => 'Alphabet browsing by default for following library items (album,artist,...)',
            'libitem_contextmenu' => 'Library item context menu',
            'librefm_challenge' => 'Libre.FM Submit Challenge',
            'listenbrainz_token' => 'ListenBrainz User Token',
            'localplay_controller' => 'Localplay Type',
            'localplay_level' => 'Localplay Access',
            'lock_songs' => 'Lock Songs',
            'matomo_site_id' => 'Matomo Site ID',
            'matomo_url' => 'Matomo URL',
            'max_bit_rate' => 'Maximum transcode bitrate for dynamic downsampling in bps (0 = disabled)',
            'min_bit_rate' => 'Minimum transcode bitrate for dynamic downsampling in bps',
            'mb_overwrite_name' => 'Overwrite Artist names that match an mbid',
            'mini_player' => 'Lock this user into the mini player interface',
            'mpd_active' => 'MPD Active Instance',
            'notify_email' => 'Allow E-mail notifications',
            'now_playing_per_user' => 'Now Playing filtered per user',
            'offset_limit' => 'Offset Limit',
            'of_the_moment' => 'Set the amount of items Album/Video of the Moment will display',
            'paypal_business' => 'PayPal ID',
            'paypal_currency_code' => 'PayPal Currency Code',
            'perpetual_api_session' => 'API sessions do not expire',
            'personalfav_display' => 'Personal favorites on the homepage',
            'personalfav_playlist' => 'Favorite Playlists',
            'personalfav_smartlist' => 'Favorite Smartlists',
            'personalfav_order' => 'Plugin CSS order',
            'piwik_site_id' => 'Piwik Site ID',
            'piwik_url' => 'Piwik URL',
            'playlist_method' => 'Playlist Method',
            'playlist_type' => 'Playlist Type',
            'play_type' => 'Playback Type',
            'podcast_keep' => '# latest episodes to keep',
            'podcast_new_download' => '# episodes to download when new episodes are available',
            'popular_threshold' => 'Popular Threshold',
            'rate_limit' => 'Download Rate Limit',
            'ratingmatch_flag_rule' => 'Match rule for Flags',
            'ratingmatch_flags' => 'When you love a track, flag the album and artist',
            'ratingmatch_star1_rule' => 'Match rule for 1 Star ($play,$skip)',
            'ratingmatch_star2_rule' => 'Match rule for 2 Stars',
            'ratingmatch_star3_rule' => 'Match rule for 3 Stars',
            'ratingmatch_star4_rule' => 'Match rule for 4 Stars',
            'ratingmatch_star5_rule' => 'Match rule for 5 Stars',
            'ratingmatch_stars' => 'Minimum star rating to match',
            'rssview_feed_url' => 'RSS Feed URL',
            'rssview_max_items' => 'RSS Feed max items',
            'rssview_order' => 'Plugin CSS order',
            'share_expire' => 'Share links default expiration days (0=never)',
            'share' => 'Allow Share',
            'shouthome_max_items' => 'Shoutbox on homepage max items',
            'shouthome_order' => 'Plugin CSS order',
            'show_album_artist' => "Show 'Album Artists' link in the main sidebar",
            'show_artist' => "Show 'Artists' link in the main sidebar",
            'show_collection' => "Show 'Collections' link in the main sidebar",
            'show_donate' => 'Show donate button in footer',
            'show_folder' => "Show 'Folders' link in the main sidebar",
            'show_header_login' => 'Show the login / registration links in the site header',
            'show_license' => 'Show License',
            'show_lyrics' => 'Show lyrics',
            'show_mood' => "Show 'Moods' link in the main sidebar",
            'show_original_year' => 'Show Album original year on links (if available)',
            'show_played_times' => 'Show # played',
            'show_playlist_media_parent' => 'Show Artist column on playlist media rows',
            'show_playlist_username' => 'Show playlist owner username in titles',
            'show_skipped_times' => 'Show # skipped',
            'show_subtitle' => 'Show Album subtitle on links (if available)',
            'show_wrapped' => 'Enable access to your personal "Spotify Wrapped" from your user page',
            'sidebar_light' => 'Light sidebar by default',
            'sidebar_hide_browse' => 'Hide the Browse menu in the sidebar',
            'sidebar_hide_dashboard' => 'Hide the Dashboard menu in the sidebar',
            'sidebar_hide_information' => 'Hide the Information menu in the sidebar',
            'sidebar_hide_playlist' => 'Hide the Playlist menu in the sidebar',
            'sidebar_hide_search' => 'Hide the Search menu in the sidebar',
            'sidebar_hide_switcher' => 'Hide sidebar switcher arrows',
            'sidebar_hide_video' => 'Hide the Video menu in the sidebar',
            'sidebar_order_browse' => 'Custom CSS Order - Browse',
            'sidebar_order_dashboard' => 'Custom CSS Order - Dashboard',
            'sidebar_order_information' => 'Custom CSS Order - Information',
            'sidebar_order_playlist' => 'Custom CSS Order - Playlist',
            'sidebar_order_search' => 'Custom CSS Order - Search',
            'sidebar_order_video' => 'Custom CSS Order - Video',
            'site_title' => 'Website Title',
            'slideshow_time' => 'Artist slideshow inactivity time',
            'song_page_title' => 'Show current song in Web player page title',
            'stats_threshold' => 'Statistics Day Threshold',
            'stream_beautiful_url' => 'Enable URL Rewriting',
            'stream_control_bandwidth_days' => 'Stream control bandwidth history (days)',
            'stream_control_bandwidth_max' => 'Stream control maximal bandwidth (month)',
            'stream_control_hits_days' => 'Stream control hits history (days)',
            'stream_control_hits_max' => 'Stream control maximal hits',
            'stream_control_time_days' => 'Stream control time history (days)',
            'stream_control_time_max' => 'Stream control maximal time (minutes)',
            'subsonic_always_download' => 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)',
            'subsonic_backend' => 'Use Subsonic backend',
            'subsonic_force_album_artist' => 'Force Album Artist for Subsonic API responses',
            'subsonic_legacy' => 'Enable legacy Subsonic API responses for compatibility issues',
            'subsonic_single_user_data' => 'Use single user data for Subsonic API responses',
            'tadb_api_key' => 'TheAudioDb API key',
            'tadb_overwrite_name' => 'Overwrite Artist names that match an mbid',
            'theme_color' => 'Theme color',
            'theme_name' => 'Theme',
            'topmenu' => 'Top menu',
            'transcode_bitrate_api' => 'Transcode bitrate - API (overrides default)',
            'transcode_bitrate_webplayer' => 'Transcode bitrate - Web Player (overrides default)',
            'transcode_bitrate' => 'Transcode bitrate - Default',
            'transcode' => 'Allow Transcoding',
            'ui_fixed' => 'Fix header position on compatible themes',
            'unique_playlist' => 'Only add unique items to playlists',
            'upload_access_level' => 'Upload Access Level',
            'upload_allow_edit' => 'Allow users to edit uploaded songs',
            'upload_allow_remove' => 'Allow users to remove uploaded songs',
            'upload_catalog_pattern' => 'Rename uploaded file according to catalog pattern',
            'upload_catalog' => 'Destination catalog',
            'upload_script' => 'Post-upload script (current directory = upload target directory)',
            'upload_subdir' => 'Create a subdirectory per user',
            'upload_user_artist' => "Consider the user sender as the track's artist",
            'upnp_active' => 'UPnP Active Instance',
            'upnp_backend' => 'Use UPnP backend',
            'use_original_year' => 'Browse by Original Year for albums (falls back to Year)',
            'vlc_active' => 'VLC Active Instance',
            'webdav_backend' => 'Use WebDAV backend',
            'webplayer_confirmclose' => 'Confirmation when closing current playing window',
            'webplayer_pausetabs' => 'Auto-pause between tabs',
            'webplayer_removeplayed' => 'Remove tracks before the current playlist item in the webplayer when played',
            'xbmc_active' => 'XBMC Active Instance',
            'yourls_api_key' => 'YOURLS API key',
            'yourls_domain' => 'YOURLS domain name',
            'yourls_use_idn' => 'YOURLS use IDN',
        ];
        self::getPreferenceRepository()->updateDescriptions($pref_array);
    }

    /**
     * update
     * This updates a single preference from the given name or id
     */
    public static function update(
        int|string $preference,
        int $user_id,
        array|int|float|string|bool|null $value,
        ?bool $applytoall = false,
        ?bool $applytodefault = false,
    ): bool {
        if ($user_id === 0) {
            return false;
        }
        $access100 = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
        // First prepare
        if (!is_numeric($preference)) {
            $pref_id = self::id_from_name($preference);
            $name    = (string) $preference;
        } else {
            $pref_id = (int) $preference;
            $name    = self::name_from_id($preference);
        }

        if (
            (
                $pref_id === null
                || $pref_id === 0
            )
            || (
                $name === null
                || $name === ''
                || $name === '0'
            )
        ) {
            return false;
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $repository = self::getPreferenceRepository();
        $preference = ($repository->hasUserPreferenceName()) ? $name : $pref_id;

        if (self::has_access($name)) {
            $repository->updateValue(
                $preference,
                $value,
                ($applytoall && $access100) ? null : $user_id,
                $applytodefault && $access100
            );
            self::clear_from_session();

            parent::remove_from_cache('get_by_user', $user_id);

            return true;
        }
        debug_event(self::class, (Core::get_global('user')->username ?? T_('Unknown')) . ' attempted to update ' . $name . ' but does not have sufficient permissions', 3);

        return false;
    }

    /**
     * update_all
     * This takes a preference id and a value and updates all users with the new info
     */
    public static function update_all(string $preference, int|string|null $value): bool
    {
        $ampacheSeven = true;
        $repository   = self::getPreferenceRepository();
        if (!$repository->hasUserPreferenceName()) {
            $ampacheSeven = false;
            $preference   = (int) self::id_from_name($preference);
        }

        $repository->updateValueForAll($preference, $value);

        parent::clear_cache();
        self::clear_from_session();

        return true;
    }

    /**
     * update_level
     * This takes a preference ID and updates the level required to update it (performed by an admin)
     */
    public static function update_level(int|string $preference, int $level): bool
    {
        // First prepare
        $preference_id = (is_numeric($preference))
            ? $preference
            : self::id_from_name($preference);

        self::getPreferenceRepository()->updateLevel((int) $preference_id, $level);

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getCatalogFilterRepository(): CatalogFilterRepositoryInterface
    {
        global $dic;

        return $dic->get(CatalogFilterRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPreferenceRepository(): PreferenceRepositoryInterface
    {
        global $dic;

        return $dic->get(PreferenceRepositoryInterface::class);
    }
}
