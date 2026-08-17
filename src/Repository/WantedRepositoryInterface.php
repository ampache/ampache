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

use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Wanted;

/**
 * @phpstan-type DatabaseRow array{
 *     id: int,
 *     user: int,
 *     artist: ?int,
 *     artist_mbid: ?string,
 *     mbid: ?string,
 *     name: ?string,
 *     year: ?int,
 *     date: int,
 *     accepted: int
 * }
 */
interface WantedRepositoryInterface
{
    /**
     * This cleans out unused wanted items
     */
    /**
     * Marks a wanted item as accepted
     */
    public function accept(string $musicbrainzId): void;

    public function collectGarbage(): void;

    /**
     * Delete wanted release.
     */
    public function deleteByMusicbrainzId(
        string $musicbrainzId,
        ?User $user = null,
    ): void;

    /**
     * Check if a release mbid is already marked as wanted
     */
    public function find(string $musicbrainzId, User $user): ?int;

    /**
     * Get wanted list.
     *
     * @return int[]
     */
    public function findAll(?User $user = null): array;

    /**
     * Find a single item by its id
     */
    public function findById(int $itemId): ?Wanted;

    /**
     * Find wanted release by mbid.
     */
    public function findByMusicBrainzId(string $mbid): ?Wanted;

    /**
     * Find wanted release by name.
     */
    public function findByName(string $name): ?Wanted;

    /**
     * Get accepted wanted release count.
     */
    public function getAcceptedCount(): int;

    /**
     * retrieves the info from the database and puts it in the cache
     *
     * @return null|DatabaseRow
     */
    public function getById(int $wantedId): ?array;

    /**
     * Returns the full rows for a set of ids, for the object cache
     *
     * @param array<int|string> $wantedIds
     * @return list<DatabaseRow>
     */
    public function getRowsByIds(array $wantedIds): array;

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrateArtist(int $oldObjectId, int $newObjectId): void;

    public function prototype(): Wanted;
}
