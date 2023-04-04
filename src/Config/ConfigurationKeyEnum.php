<?php

declare(strict_types=1);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
    public const ACCESS_CONTROL                        = 'access_control';
    public const ADDITIONAL_DELIMITERS                 = 'additional_genre_delimiters';
    public const ADMIN_ENABLE_REQUIRED                 = 'admin_enable_required';
    public const AJAX_LOAD                             = 'ajax_load';
    public const ALBUM_ART_MAX_HEIGHT                  = 'album_art_max_height';
    public const ALBUM_ART_MAX_WIDTH                   = 'album_art_max_width';
    public const ALBUM_ART_MIN_HEIGHT                  = 'album_art_min_height';
    public const ALBUM_ART_MIN_WIDTH                   = 'album_art_min_width';
    public const ALBUM_ART_PREFERRED_FILENAME          = 'album_art_preferred_filename';
    public const ALBUM_GROUP                           = 'album_group';
    public const ALBUM_RELEASE_TYPE                    = 'album_release_type';
    public const ALBUM_RELEASE_TYPE_SORT               = 'album_release_type_sort';
    public const ALBUM_SORT                            = 'album_sort';
    public const ALLOW_DEMOCRATIC_PLAYBACK             = 'allow_democratic_playback';
    public const ALLOWED_ZIP_TYPES                     = 'allow_zip_types';
    public const ALLOW_LOCALPLAY_PLAYBACK              = 'allow_localplay_playback';
    public const ALLOW_PERSONAL_INFO_AGENT             = 'allow_personal_info_agent';
    public const ALLOW_PERSONAL_INFO_NOW               = 'allow_personal_info_now';
    public const ALLOW_PERSONAL_INFO_RECENT            = 'allow_personal_info_recent';
    public const ALLOW_PERSONAL_INFO_TIME              = 'allow_personal_info_time';
    public const ALLOW_PUBLIC_REGISTRATION             = 'allow_public_registration';
    public const ALLOW_STREAM_PLAYBACK                 = 'allow_stream_playback';
    public const ALLOW_UPLOAD                          = 'allow_upload';
    public const ALLOW_VIDEO                           = 'allow_video';
    public const ALLOW_ZIP_DOWNLOAD                    = 'allow_zip_download';
    public const API_ENABLE_3                          = 'api_enable_3';
    public const API_ENABLE_4                          = 'api_enable_4';
    public const API_ENABLE_5                          = 'api_enable_5';
    public const API_ENABLE_6                          = 'api_enable_6';
    public const API_FORCE_VERSION                     = 'api_force_version';
    public const API_HIDDEN_PLAYLISTS                  = 'api_hidden_playlists';
    public const ART_ZIP_ADD                           = 'art_zip_add';
    public const AUTH_METHODS                          = 'auth_methods';
    public const AUTH_PASSWORD_SAVE                    = 'auth_password_save';
    public const AUTO_CREATE                           = 'auto_create';
    public const AUTOUPDATE                            = 'autoupdate';
    public const AUTOUPDATE_LASTCHECK                  = 'autoupdate_lastcheck';
    public const AUTOUPDATE_LASTVERSION                = 'autoupdate_lastversion';
    public const AUTOUPDATE_LASTVERSION_NEW            = 'autoupdate_lastversion_new';
    public const AUTO_USER                             = 'auto_user';
    public const BACKEND_WEBDAV                        = 'webdav_backend';
    public const BROADCAST                             = 'broadcast';
    public const BROADCAST_BY_DEFAULT                  = 'broadcast_by_default';
    public const BROWSE_FILTER                         = 'browse_filter';
    public const BROWSER_NOTIFY                        = 'browser_notify';
    public const BROWSER_NOTIFY_TIMEOUT                = 'browser_notify_timeout';
    public const CAPTCHA_PUBLIC_REG                    = 'captcha_public_reg';
    public const CATALOG_CHECK_DUPLICATE               = 'catalog_check_duplicate';
    public const CATALOG_DISABLE                       = 'catalog_disable';
    public const CATALOG_FILTER                        = 'catalog_filter';
    public const COMMON_ABBR                           = 'common_abbr';
    public const COMPOSER_BINARY_PATH                  = 'composer_binary_path';
    public const CRON_CACHE                            = 'cron_cache';
    public const CUSTOM_BLANKALBUM                     = 'custom_blankalbum';
    public const CUSTOM_BLANKMOVIE                     = 'custom_blankmovie';
    public const CUSTOM_DATETIME                       = 'custom_datetime';
    public const CUSTOM_FAVICON                        = 'custom_favicon';
    public const CUSTOM_LOGIN_BACKGROUND               = 'custom_login_background';
    public const CUSTOM_LOGIN_LOGO                     = 'custom_login_logo';
    public const CUSTOM_LOGO                           = 'custom_logo';
    public const CUSTOM_TEXT_FOOTER                    = 'custom_text_footer';
    public const DAAP_BACKEND                          = 'daap_backend';
    public const DAAP_PASS                             = 'daap_pass';
    public const DEBUG_MODE                            = 'debug';
    public const DEMO_CLEAR_SESSIONS                   = 'demo_clear_sessions';
    public const DEMO_USE_SEARCH                       = 'demo_use_search';
    public const DEMO_MODE                             = 'demo_mode';
    public const DIRECTPLAY                            = 'directplay';
    public const DIRECT_PLAY_LIMIT                     = 'direct_play_limit';
    public const DISABLED_CUSTOM_METADATA_FIELDS       = 'disabled_custom_metadata_fields';
    public const DISABLED_CUSTOM_METADATA_FIELDS_INPUT = 'disabled_custom_metadata_fields_input';
    public const DOWNLOAD                              = 'download';
    public const ENABLE_CUSTOM_METADATA                = 'enable_custom_metadata';
    public const EXTERNAL_AUTO_UPDATE                  = 'external_auto_update';
    public const FILE_ZIP_COMMENT                      = 'file_zip_comment';
    public const FORCE_HTTP_PLAY                       = 'force_http_play';
    public const GEOLOCATION                           = 'geolocation';
    public const GETID3_DETECT_ID3V2_ENCODING          = 'getid3_detect_id3v2_encoding';
    public const GETID3_TAG_ORDER                      = 'getid3_tag_order';
    public const HIDE_GENRES                           = 'hide_genres';
    public const HIDE_SINGLE_ARTIST                    = 'hide_single_artist';
    public const HOME_MOMENT_ALBUMS                    = 'home_moment_albums';
    public const HOME_MOMENT_VIDEOS                    = 'home_moment_videos';
    public const HOME_NOW_PLAYING                      = 'home_now_playing';
    public const HOME_RECENTLY_PLAYED                  = 'home_recently_played';
    public const HTTPQ_ACTIVE                          = 'httpq_active';
    public const LABEL                                 = 'label';
    public const LANG                                  = 'lang';
    public const LASTFM_CHALLENGE                      = 'lastfm_challenge';
    public const LASTFM_GRANT_LINK                     = 'lastfm_grant_link';
    public const LIBITEM_BROWSE_ALPHA                  = 'libitem_browse_alpha';
    public const LIBITEM_CONTEXTMENU                   = 'libitem_contextmenu';
    public const LICENSING                             = 'licensing';
    public const LOCALPLAY_CONTROLLER                  = 'localplay_controller';
    public const LOCALPLAY_LEVEL                       = 'localplay_level';
    public const LOCK_SONGS                            = 'lock_songs';
    public const MB_DETECT_ORDER                       = 'mb_detect_order';
    public const METADATA_ORDER                        = 'metadata_order';
    public const METADATA_ORDER_VIDEO                  = 'metadata_order_video';
    public const MPD_ACTIVE                            = 'mpd_active';
    public const NOTIFY_EMAIL                          = 'notify_email';
    public const NOW_PLAYING_CSS_FILE                  = 'now_playing_css_file';
    public const NOW_PLAYING_PER_USER                  = 'now_playing_per_user';
    public const NOW_PLAYING_REFRESH_LIMIT             = 'now_playing_refresh_limit';
    public const OFFSET_LIMIT                          = 'offset_limit';
    public const OF_THE_MOMENT                         = 'of_the_moment';
    public const PLAYLIST_METHOD                       = 'playlist_method';
    public const PLAYLIST_TYPE                         = 'playlist_type';
    public const PLAY_TYPE                             = 'play_type';
    public const PODCAST_KEEP                          = 'podcast_keep';
    public const PODCAST_NEW_DOWNLOAD                  = 'podcast_new_download';
    public const PODCAST                               = 'podcast';
    public const POPULAR_THRESHOLD                     = 'popular_threshold';
    public const RADIO                                 = 'live_stream';
    public const RATE_LIMIT                            = 'rate_limit';
    public const RATING_FILE_TAG_USER                  = 'rating_file_tag_user';
    public const RATINGS                               = 'ratings';
    public const RAW_WEB_PATH                          = 'raw_web_path';
    public const REFRESH_LIMIT                         = 'refresh_limit';
    public const REGISTRATION_MANDATORY_FIELDS         = 'registration_mandatory_fields';
    public const REQUIRE_SESSION                       = 'require_session';
    public const RESIZE_IMAGES                         = 'resize_images';
    public const SESSION_NAME                          = 'session_name';
    public const SHARE_EXPIRE                          = 'share_expire';
    public const SHARE                                 = 'share';
    public const SHOW_DONATE                           = 'show_donate';
    public const SHOW_LICENSE                          = 'show_license';
    public const SHOW_LYRICS                           = 'show_lyrics';
    public const SHOW_PLAYED_TIMES                     = 'show_played_times';
    public const SHOW_PLAYLIST_USERNAME                = 'show_playlist_username';
    public const SHOW_SKIPPED_TIMES                    = 'show_skipped_times';
    public const SIDEBAR_LIGHT                         = 'sidebar_light';
    public const SIMPLE_USER_MODE                      = 'simple_user_mode';
    public const SITE_CHARSET                          = 'site_charset';
    public const SITE_TITLE                            = 'site_title';
    public const SLIDESHOW_TIME                        = 'slideshow_time';
    public const SOCIABLE                              = 'sociable';
    public const SONG_PAGE_TITLE                       = 'song_page_title';
    public const STATISTICAL_GRAPHS                    = 'statistical_graphs';
    public const STATS_THRESHOLD                       = 'stats_threshold';
    public const STREAM_BEAUTIFUL_URL                  = 'stream_beautiful_url';
    public const SUBSONIC_ALWAYS_DOWNLOAD              = 'subsonic_always_download';
    public const SUBSONIC_BACKEND                      = 'subsonic_backend';
    public const TAG_ORDER                             = 'tag_order';
    public const THEME_COLOR                           = 'theme_color';
    public const THEME_NAME                            = 'theme_name';
    public const THEME_PATH                            = 'theme_path';
    public const TOPMENU                               = 'topmenu';
    public const TRACK_USER_IP                         = 'track_user_ip';
    public const TRANSCODE_BITRATE                     = 'transcode_bitrate';
    public const TRANSCODE                             = 'transcode';
    public const UI_FIXED                              = 'ui_fixed';
    public const UNIQUE_PLAYLIST                       = 'unique_playlist';
    public const UPLOAD_ACCESS_LEVEL                   = 'upload_access_level';
    public const UPLOAD_ALLOW_EDIT                     = 'upload_allow_edit';
    public const UPLOAD_ALLOW_REMOVE                   = 'upload_allow_remove';
    public const UPLOAD_CATALOG_PATTERN                = 'upload_catalog_pattern';
    public const UPLOAD_CATALOG                        = 'upload_catalog';
    public const UPLOAD_SCRIPT                         = 'upload_script';
    public const UPLOAD_SUBDIR                         = 'upload_subdir';
    public const UPLOAD_USER_ARTIST                    = 'upload_user_artist';
    public const USE_AUTH                              = 'use_auth';
    public const USE_NOW_PLAYING_EMBEDDED              = 'use_now_playing_embedded';
    public const USER_AGREEMENT                        = 'user_agreement';
    public const USER_NO_EMAIL_CONFIRM                 = 'user_no_email_confirm';
    public const USE_RSS                               = 'use_rss';
    public const VERSION                               = 'version';
    public const WAVEFORM                              = 'waveform';
    public const WEB_PATH                              = 'web_path';
    public const WRITE_TAGS                            = 'write_tags';
}
