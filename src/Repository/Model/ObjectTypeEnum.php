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
 *
 */

namespace Ampache\Repository\Model;

/**
 * Defines all available object-types
 */
enum ObjectTypeEnum: string
{
    case ALBUM           = 'album';
    case ALBUM_ARTIST    = 'album_artist';
    case ALBUM_DISK      = 'album_disk';
    case ART             = 'art';
    case ARTIST          = 'artist';
    case BOOKMARK        = 'bookmark';
    case BROADCAST       = 'broadcast';
    case GENRE           = 'genre';
    case LABEL           = 'label';
    case LIVE_STREAM     = 'live_stream';
    case PLAYLIST        = 'playlist';
    case PODCAST         = 'podcast';
    case PODCAST_EPISODE = 'podcast_episode';
    case PRIVATE_MESSAGE = 'private_message';
    case SEARCH          = 'search';
    case SHARE           = 'share';
    case SONG_ARTIST     = 'song_artist';
    case SONG            = 'song';
    case SONG_PREVIEW    = 'song_preview';
    case TAG_HIDDEN      = 'tag_hidden';
    case TAG             = 'tag';
    case USER            = 'user';
    case VIDEO           = 'video';
    case WANTED          = 'wanted';
}
