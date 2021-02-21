<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

namespace Ampache\Repository;

interface WantedRepositoryInterface
{
    /**
     * Get wanted list.
     *
     * @return int[]
     */
    public function getAll(?int $userId): array;

    /**
     * Check if a release mbid is already marked as wanted
     */
    public function find(string $musicbrainzId, int $userId): ?int;

    /**
     * Delete wanted release.
     */
    public function deleteByMusicbrainzId(
        string $musicbrainzId,
        ?int $userId
    ): void;

    /**
     * Get accepted wanted release count.
     */
    public function getAcceptedCount(): int;

    /**
     * retrieves the info from the database and puts it in the cache
     *
     * @return array<string, mixed>
     */
    public function getById(int $wantedId): array;

    /**
     * Adds a new wanted entry
     */
    public function add(
        string $mbid,
        int $artist,
        string $artist_mbid,
        string $name,
        int $year,
        int $userId,
        bool $accept
    ): void;

    /**
     * Get wanted release by mbid.
     */
    public function getByMusicbrainzId(string $musicbrainzId): int;
}
