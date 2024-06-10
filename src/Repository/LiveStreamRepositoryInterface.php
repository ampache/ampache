<?php

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

namespace Ampache\Repository;

use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\User;

interface LiveStreamRepositoryInterface
{
    /**
     * Returns all items
     *
     * If a user is provided, the result will be limited to catalogs the user has access to
     *
     * @return list<int>
     */
    public function findAll(
        ?User $user = null
    ): array;

    /**
     * Finds a single item by its id
     */
    public function findById(int $objectId): ?Live_Stream;

    /**
     * This deletes the object with the given id from the database
     */
    public function delete(Live_Stream $liveStream): void;
}
