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

use Ampache\Repository\AlbumRepositoryInterface;

/**
 * The `album` columns a single-field write is allowed to target.
 *
 * The column name is interpolated into the statement, so this is what stops it being caller-supplied.
 *
 * @see AlbumRepositoryInterface::setField()
 */
enum AlbumFieldEnum: string
{
    case ALBUM_ARTIST   = 'album_artist';
    case BARCODE        = 'barcode';
    case CATALOG        = 'catalog';
    case CATALOG_NUMBER = 'catalog_number';
    case MBID           = 'mbid';
    case MBID_GROUP     = 'mbid_group';
    case NAME           = 'name';
    case ORIGINAL_YEAR  = 'original_year';
    case PREFIX         = 'prefix';
    case RELEASE_STATUS = 'release_status';
    case RELEASE_TYPE   = 'release_type';
    case VERSION        = 'version';
    case YEAR           = 'year';
}
