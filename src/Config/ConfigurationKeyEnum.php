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

namespace Ampache\Config;

/**
 * This class contains constants for all available configuration keys
 */
final class ConfigurationKeyEnum
{
    public const string ACCESS_CONTROL                        = 'access_control';
    public const string ADDITIONAL_DELIMITERS                 = 'additional_genre_delimiters';
    public const string ADMIN_ENABLE_REQUIRED                 = 'admin_enable_required';
    public const string AJAX_LOAD                             = 'ajax_load';
    public const string ALBUM_ART_MAX_HEIGHT                  = 'album_art_max_height';
    public const string ALBUM_ART_MAX_WIDTH                   = 'album_art_max_width';
    public const string ALBUM_ART_MIN_HEIGHT                  = 'album_art_min_height';
    public const string ALBUM_ART_MIN_WIDTH                   = 'album_art_min_width';
    public const string ALBUM_ART_PREFERRED_FILENAME          = 'album_art_preferred_filename';
    public const string ALBUM_GROUP                           = 'album_group';
    public const string ALBUM_RELEASE_TYPE                    = 'album_release_type';
    public const string ALBUM_RELEASE_TYPE_SORT               = 'album_release_type_sort';
    public const string ALBUM_SORT                            = 'album_sort';
    public const string ALLOW_DEMOCRATIC_PLAYBACK             = 'allow_democratic_playback';
    public const string ALLOWED_ZIP_TYPES                     = 'allow_zip_types';
    public const string ALLOW_LOCALPLAY_PLAYBACK              = 'allow_localplay_playback';
    public const string ALLOW_PERSONAL_INFO_AGENT             = 'allow_personal_info_agent';
    public const string ALLOW_PERSONAL_INFO_NOW               = 'allow_personal_info_now';
    public const string ALLOW_PERSONAL_INFO_RECENT            = 'allow_personal_info_recent';
    public const string ALLOW_PERSONAL_INFO_TIME              = 'allow_personal_info_time';
    public const string ALLOW_PUBLIC_REGISTRATION             = 'allow_public_registration';
    public const string ALLOW_STREAM_PLAYBACK                 = 'allow_stream_playback';
    public const string ALLOW_UPLOAD                          = 'allow_upload';
    public const string ALLOW_VIDEO                           = 'allow_video';
    public const string ALLOW_ZIP_DOWNLOAD                    = 'allow_zip_download';
    public const string API_ENABLE_3                          = 'api_enable_3';
    public const string API_ENABLE_4                          = 'api_enable_4';
    public const string API_ENABLE_5                          = 'api_enable_5';
    public const string API_ENABLE_6                          = 'api_enable_6';
    public const string API_ENABLE_8                          = 'api_enable_8';
    public const string API_FORCE_VERSION                     = 'api_force_version';
    public const string API_HIDDEN_PLAYLISTS                  = 'api_hidden_playlists';
    public const string ART_ZIP_ADD                           = 'art_zip_add';
    public const string AUTH_METHODS                          = 'auth_methods';
    public const string AUTH_PASSWORD_SAVE                    = 'auth_password_save';
    public const string AUTO_CREATE                           = 'auto_create';
    public const string AUTOUPDATE                            = 'autoupdate';
    public const string AUTOUPDATE_LASTCHECK                  = 'autoupdate_lastcheck';
    public const string AUTOUPDATE_LASTVERSION                = 'autoupdate_lastversion';
    public const string AUTOUPDATE_LASTVERSION_NEW            = 'autoupdate_lastversion_new';
    public const string AUTO_USER                             = 'auto_user';
    public const string BACKEND_WEBDAV                        = 'webdav_backend';
    public const string BROADCAST                             = 'broadcast';
    public const string BROADCAST_BY_DEFAULT                  = 'broadcast_by_default';
    public const string BROWSE_FILTER                         = 'browse_filter';
    public const string BROWSER_NOTIFY                        = 'browser_notify';
    public const string BROWSER_NOTIFY_TIMEOUT                = 'browser_notify_timeout';
    public const string CAPTCHA_PUBLIC_REG                    = 'captcha_public_reg';
    public const string CATALOG_CHECK_DUPLICATE               = 'catalog_check_duplicate';
    public const string CATALOG_DISABLE                       = 'catalog_disable';
    public const string CATALOG_FILTER                        = 'catalog_filter';
    public const string COMMON_ABBR                           = 'common_abbr';
    public const string COMPOSER_BINARY_PATH                  = 'composer_binary_path';
    public const string COMPOSER_NO_DEV                       = 'composer_no_dev';
    public const string CRON_CACHE                            = 'cron_cache';
    public const string CUSTOM_BLANKALBUM                     = 'custom_blankalbum';
    public const string CUSTOM_DATETIME                       = 'custom_datetime';
    public const string CUSTOM_FAVICON                        = 'custom_favicon';
    public const string CUSTOM_LOGIN_BACKGROUND               = 'custom_login_background';
    public const string CUSTOM_LOGIN_LOGO                     = 'custom_login_logo';
    public const string CUSTOM_LOGO                           = 'custom_logo';
    public const string CUSTOM_TEXT_FOOTER                    = 'custom_text_footer';
    public const string DAAP_BACKEND                          = 'daap_backend';
    public const string DAAP_PASS                             = 'daap_pass';
    public const string DEBUG_MODE                            = 'debug';
    public const string DELETE_FROM_DISK                      = 'delete_from_disk';
    public const string DEMO_CLEAR_SESSIONS                   = 'demo_clear_sessions';
    public const string DEMO_USE_SEARCH                       = 'demo_use_search';
    public const string DEMO_MODE                             = 'demo_mode';
    public const string DIRECTPLAY                            = 'directplay';
    public const string DIRECT_PLAY_LIMIT                     = 'direct_play_limit';
    public const string DISABLED_CUSTOM_METADATA_FIELDS       = 'disabled_custom_metadata_fields';
    public const string DISABLED_CUSTOM_METADATA_FIELDS_INPUT = 'disabled_custom_metadata_fields_input';
    public const string DOWNLOAD                              = 'download';
    public const string ENABLE_CUSTOM_METADATA                = 'enable_custom_metadata';
    public const string EXTERNAL_AUTO_UPDATE                  = 'external_auto_update';
    public const string EXTENDED_PLAYLIST_LINKS               = 'extended_playlist_links';
    public const string EXTERNAL_LINKS_GOOGLE                 = 'external_links_google';
    public const string EXTERNAL_LINKS_DUCKDUCKGO             = 'external_links_duckduckgo';
    public const string EXTERNAL_LINKS_WIKIPEDIA              = 'external_links_wikipedia';
    public const string EXTERNAL_LINKS_LASTFM                 = 'external_links_lastfm';
    public const string EXTERNAL_LINKS_BANDCAMP               = 'external_links_bandcamp';
    public const string EXTERNAL_LINKS_MUSICBRAINZ            = 'external_links_musicbrainz';
    public const string FILE_ZIP_COMMENT                      = 'file_zip_comment';
    public const string FORCE_HTTP_PLAY                       = 'force_http_play';
    public const string GEOLOCATION                           = 'geolocation';
    public const string GETID3_DETECT_ID3V2_ENCODING          = 'getid3_detect_id3v2_encoding';
    public const string GETID3_TAG_ORDER                      = 'getid3_tag_order';
    public const string HIDE_GENRES                           = 'hide_genres';
    public const string HIDE_SINGLE_ARTIST                    = 'hide_single_artist';
    public const string HOME_MOMENT_ALBUMS                    = 'home_moment_albums';
    public const string HOME_MOMENT_VIDEOS                    = 'home_moment_videos';
    public const string HOME_NOW_PLAYING                      = 'home_now_playing';
    public const string HOME_RECENTLY_PLAYED                  = 'home_recently_played';
    public const string HTTPQ_ACTIVE                          = 'httpq_active';
    public const string LABEL                                 = 'label';
    public const string LANG                                  = 'lang';
    public const string LASTFM_CHALLENGE                      = 'lastfm_challenge';
    public const string LASTFM_GRANT_LINK                     = 'lastfm_grant_link';
    public const string LIBITEM_BROWSE_ALPHA                  = 'libitem_browse_alpha';
    public const string LIBITEM_CONTEXTMENU                   = 'libitem_contextmenu';
    public const string LICENSING                             = 'licensing';
    public const string LOCAL_METADATA_DIR                    = 'local_metadata_dir';
    public const string LOCALPLAY_CONTROLLER                  = 'localplay_controller';
    public const string LOCALPLAY_LEVEL                       = 'localplay_level';
    public const string LOCK_SONGS                            = 'lock_songs';
    public const string MB_DETECT_ORDER                       = 'mb_detect_order';
    public const string METADATA_ORDER                        = 'metadata_order';
    public const string METADATA_ORDER_VIDEO                  = 'metadata_order_video';
    public const string MPD_ACTIVE                            = 'mpd_active';
    public const string NOTIFY_EMAIL                          = 'notify_email';
    public const string NOW_PLAYING_CSS_FILE                  = 'now_playing_css_file';
    public const string NOW_PLAYING_PER_USER                  = 'now_playing_per_user';
    public const string NOW_PLAYING_REFRESH_LIMIT             = 'now_playing_refresh_limit';
    public const string NPM_BINARY_PATH                       = 'npm_binary_path';
    public const string OFFSET_LIMIT                          = 'offset_limit';
    public const string OF_THE_MOMENT                         = 'of_the_moment';
    public const string PERPETUAL_API_SESSION                 = 'perpetual_api_session';
    public const string PLAYLIST_METHOD                       = 'playlist_method';
    public const string PLAYLIST_TYPE                         = 'playlist_type';
    public const string PLAY_TYPE                             = 'play_type';
    public const string PODCAST_KEEP                          = 'podcast_keep';
    public const string PODCAST_NEW_DOWNLOAD                  = 'podcast_new_download';
    public const string PODCAST                               = 'podcast';
    public const string POPULAR_THRESHOLD                     = 'popular_threshold';
    public const string PROXY_HOST                            = 'proxy_host';
    public const string PROXY_PASS                            = 'proxy_pass';
    public const string PROXY_PORT                            = 'proxy_port';
    public const string PROXY_USER                            = 'proxy_user';
    public const string PUBLIC_IMAGES                         = 'public_images';
    public const string RADIO                                 = 'live_stream';
    public const string RATE_LIMIT                            = 'rate_limit';
    public const string RATING_FILE_TAG_USER                  = 'rating_file_tag_user';
    public const string RATINGS                               = 'ratings';
    public const string RAW_WEB_PATH                          = 'raw_web_path';
    public const string REFRESH_LIMIT                         = 'refresh_limit';
    public const string REGISTRATION_MANDATORY_FIELDS         = 'registration_mandatory_fields';
    public const string REQUIRE_SESSION                       = 'require_session';
    public const string RESIZE_IMAGES                         = 'resize_images';
    public const string SESSION_NAME                          = 'session_name';
    public const string SHARE_EXPIRE                          = 'share_expire';
    public const string SHARE                                 = 'share';
    public const string SHOW_DONATE                           = 'show_donate';
    public const string SHOW_LICENSE                          = 'show_license';
    public const string SHOW_LYRICS                           = 'show_lyrics';
    public const string SHOW_PLAYED_TIMES                     = 'show_played_times';
    public const string SHOW_PLAYLIST_USERNAME                = 'show_playlist_username';
    public const string SHOW_SKIPPED_TIMES                    = 'show_skipped_times';
    public const string SHOW_WRAPPED                          = 'show_wrapped';
    public const string SIDEBAR_LIGHT                         = 'sidebar_light';
    public const string SIMPLE_USER_MODE                      = 'simple_user_mode';
    public const string SITE_CHARSET                          = 'site_charset';
    public const string SITE_TITLE                            = 'site_title';
    public const string SLIDESHOW_TIME                        = 'slideshow_time';
    public const string SOCIABLE                              = 'sociable';
    public const string SONG_PAGE_TITLE                       = 'song_page_title';
    public const string STATISTICAL_GRAPHS                    = 'statistical_graphs';
    public const string STATS_THRESHOLD                       = 'stats_threshold';
    public const string STREAM_BEAUTIFUL_URL                  = 'stream_beautiful_url';
    public const string SUBSONIC_ALWAYS_DOWNLOAD              = 'subsonic_always_download';
    public const string SUBSONIC_BACKEND                      = 'subsonic_backend';
    public const string TAG_ORDER                             = 'tag_order';
    public const string THEME_COLOR                           = 'theme_color';
    public const string THEME_NAME                            = 'theme_name';
    public const string THEME_PATH                            = 'theme_path';
    public const string TOPMENU                               = 'topmenu';
    public const string TRACK_USER_IP                         = 'track_user_ip';
    public const string TRANSCODE_BITRATE                     = 'transcode_bitrate';
    public const string TRANSCODE                             = 'transcode';
    public const string UI_FIXED                              = 'ui_fixed';
    public const string UNIQUE_PLAYLIST                       = 'unique_playlist';
    public const string UPLOAD_ACCESS_LEVEL                   = 'upload_access_level';
    public const string UPLOAD_ALLOW_EDIT                     = 'upload_allow_edit';
    public const string UPLOAD_ALLOW_REMOVE                   = 'upload_allow_remove';
    public const string UPLOAD_CATALOG_PATTERN                = 'upload_catalog_pattern';
    public const string UPLOAD_CATALOG                        = 'upload_catalog';
    public const string UPLOAD_SCRIPT                         = 'upload_script';
    public const string UPLOAD_SUBDIR                         = 'upload_subdir';
    public const string UPLOAD_USER_ARTIST                    = 'upload_user_artist';
    public const string USE_AUTH                              = 'use_auth';
    public const string USE_NOW_PLAYING_EMBEDDED              = 'use_now_playing_embedded';
    public const string USER_AGREEMENT                        = 'user_agreement';
    public const string USER_NO_EMAIL_CONFIRM                 = 'user_no_email_confirm';
    public const string USE_RSS                               = 'use_rss';
    public const string VERSION                               = 'version';
    public const string WAVEFORM                              = 'waveform';
    public const string WEB_PATH                              = 'web_path';
    public const string WRITE_TAGS                            = 'write_tags';
    public const string ALBUM_ART_STORE_DISK                  = 'album_art_store_disk';
    public const string SHOW_SONG_ART                         = 'show_song_art';
}
