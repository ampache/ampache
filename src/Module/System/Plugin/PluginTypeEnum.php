<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\System\Plugin;

/**
 * Defines all available plugin-types
 */
enum PluginTypeEnum: string
{
    case URL_SHORTENER                = 'shorten';
    case HOMEPAGE_WIDGET              = 'display_home';
    case USER_FIELD_WIDGET            = 'display_user_field';
    case GEO_LOCATION                 = 'get_location_name';
    case SAVE_MEDIAPLAY               = 'save_mediaplay';
    case ART_RETRIEVER                = 'gather_arts';
    case EXTERNAL_SHARE               = 'external_share';
    case GEO_MAP                      = 'display_map';
    case SLIDESHOW                    = 'get_photos';
    case FOOTER_WIDGET                = 'display_on_footer';
    case METADATA_RETRIEVER           = 'get_metadata';
    case WANTED_LOOKUP                = 'process_wanted';
    case EXTERNAL_METADATA_RETRIEVER  = 'get_external_metadata';
    case RATING_SAVER                 = 'save_rating';
    case LYRIC_RETRIEVER              = 'get_lyrics';
    case AVATAR_PROVIDER              = 'get_avatar_url';
    case STREAM_CONTROLLER            = 'stream_control';
    case USER_FLAG_MANAGER            = 'set_flag';
    case SONG_PREVIEW_STREAM_PROVIDER = 'stream_song_preview';
    case SONG_PREVIEW_PROVIDER        = 'get_song_preview';
}
