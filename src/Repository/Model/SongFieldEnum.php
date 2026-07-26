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

/**
 * The `song` columns the edit paths are allowed to write
 *
 * The repository interpolates the case value straight into the statement, so this list is what stops
 * that being an arbitrary column name. Add a case rather than widening the writer's signature.
 */
enum SongFieldEnum: string
{
    case ALBUM       = 'album';
    case ARTIST      = 'artist';
    case BITRATE     = 'bitrate';
    case COMPOSER    = 'composer';
    case DISK        = 'disk';
    case ENABLED     = 'enabled';
    case LICENSE     = 'license';
    case MBID        = 'mbid';
    case MODE        = 'mode';
    case PLAYED      = 'played';
    case RATE        = 'rate';
    case SIZE        = 'size';
    case TIME        = 'time';
    case TITLE       = 'title';
    case TRACK       = 'track';
    case USER_UPLOAD = 'user_upload';
    case YEAR        = 'year';
}
