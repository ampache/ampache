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

namespace Ampache\Repository;

use Ampache\Repository\Model\ObjectNameTypeEnum;

/**
 * Resolves the display name of a set of objects, whichever column their type keeps it in
 *
 * This is the one reader that spans every type rather than owning a table: an album and an artist carry
 * a prefix to concatenate, a song or a video carries a title, a share carries a description.
 */
interface ObjectNameRepositoryInterface
{
    /**
     * Reads the id and display name of each object of one type, optionally ordered
     *
     * `$sort` is expected to have already been checked against the browse type's own sort list; anything
     * that is not a bare column identifier is dropped rather than ordered by. `$order` is `ASC` unless it
     * is exactly `DESC`.
     *
     * @param list<int|string> $objectIds
     * @return list<array{id: int|string, name: string}>
     */
    public function findNames(
        ObjectNameTypeEnum $type,
        array $objectIds,
        ?string $sort = null,
        string $order = 'ASC',
    ): array;
}
