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

namespace Ampache\Module\Catalog;

/**
 * The tables whose row totals are cached in `update_info`
 *
 * Each case is both a real table name and the `update_info`.`key` its total is stored under — the two
 * must stay identical, which is the invariant a plain string argument could not enforce. A smartlist is
 * counted as `search`; there is no `smartlist` table, and passing that string used to fail silently.
 */
enum CountableTableEnum: string
{
    case ALBUM           = 'album';
    case ALBUM_DISK      = 'album_disk';
    case ARTIST          = 'artist';
    case CATALOG         = 'catalog';
    case LABEL           = 'label';
    case LICENSE         = 'license';
    case LIVE_STREAM     = 'live_stream';
    case PLAYLIST        = 'playlist';
    case PODCAST         = 'podcast';
    case PODCAST_EPISODE = 'podcast_episode';
    case SEARCH          = 'search';
    case SHARE           = 'share';
    case SONG            = 'song';
    case TAG             = 'tag';
    case USER            = 'user';
    case VIDEO           = 'video';

    /**
     * Whether the table's rows carry the catalog they belong to, so the count can be narrowed to one
     *
     * `artist` is shared across catalogs and has no column of its own; narrowing it means an EXISTS
     * probe against `catalog_map`, which no caller needs yet. The rest simply are not catalog-scoped.
     */
    public function hasCatalogColumn(): bool
    {
        return match ($this) {
            self::ALBUM, self::ALBUM_DISK, self::LIVE_STREAM, self::PODCAST, self::PODCAST_EPISODE, self::SONG, self::VIDEO => true,
            self::ARTIST, self::CATALOG, self::LABEL, self::LICENSE, self::PLAYLIST, self::SEARCH, self::SHARE, self::TAG, self::USER => false,
        };
    }
}
