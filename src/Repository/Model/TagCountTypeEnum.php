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

use Ampache\Repository\TagRepositoryInterface;

/**
 * The object types the `tag` table keeps a denormalised counter column for.
 *
 * `tag_map` accepts every library item, but only these four have a column on `tag` to count into, which is why
 * tagging anything else leaves no counter behind. The name becomes a column, so it cannot be caller-supplied.
 *
 * @see TagRepositoryInterface::incrementCount()
 */
enum TagCountTypeEnum: string
{
    case ALBUM  = 'album';
    case ARTIST = 'artist';
    case SONG   = 'song';
    case VIDEO  = 'video';
}
