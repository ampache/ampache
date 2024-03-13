<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Repository\Model\Artist;

interface ArtistRepositoryInterface
{
    /**
     * Deletes the artist entry
     */
    public function delete(
        Artist $artist
    ): void;

    /**
     * This returns a number of random artists.
     *
     * @return list<int>
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array;

    /**
     * This cleans out unused artists
     */
    public function collectGarbage(): void;

    /**
     * This finds an artist based on its name
     */
    public function findByName(string $name): ?Artist;
}
