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

namespace Ampache\Repository\Model;

use Ampache\Repository\ObjectNameRepositoryInterface;

/**
 * The types a display name can be resolved for, which is not the same set as `LibraryItemEnum`.
 *
 * `album_artist` and `song_artist` are roles rather than tables, and `playlist_search` is the merged
 * playlist/smartlist list the API reports as one; none of the three is an object type in its own right.
 *
 * @see ObjectNameRepositoryInterface::findNames()
 */
enum ObjectNameTypeEnum: string
{
    case ALBUM           = 'album';
    case ALBUM_ARTIST    = 'album_artist';
    case ARTIST          = 'artist';
    case CATALOG         = 'catalog';
    case LIVE_STREAM     = 'live_stream';
    case PLAYLIST        = 'playlist';
    case PLAYLIST_SEARCH = 'playlist_search';
    case PODCAST         = 'podcast';
    case PODCAST_EPISODE = 'podcast_episode';
    case SEARCH          = 'search';
    case SHARE           = 'share';
    case SONG            = 'song';
    case SONG_ARTIST     = 'song_artist';
    case VIDEO           = 'video';
}
