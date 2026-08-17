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

use Ampache\Repository\Model\Label;
use DateTimeInterface;

interface LabelRepositoryInterface
{
    /**
     * Associate a label with an album, ignoring a pairing that is already recorded
     */
    public function addAlbumAssoc(int $labelId, int $albumId, DateTimeInterface $date): void;

    public function addArtistAssoc(int $labelId, int $artistId, DateTimeInterface $date): void;

    /**
     * This cleans out unused labels
     */
    public function collectGarbage(): void;

    public function delete(int $labelId): void;

    public function findById(int $labelId): ?Label;

    /**
     * Returns the ids of every album associated with the label
     *
     * @return int[]
     */
    public function getAlbums(Label $label): array;

    /**
     * Return the list of all available labels
     *
     * @return string[]
     */
    public function getAll(): array;

    /**
     * Counts each label's associated artists in one statement instead of one query per label
     *
     * @param array<int|string> $labelIds
     * @return array<int, int>
     */
    public function getArtistCountsByIds(array $labelIds): array;

    /**
     * Returns the ids of every artist associated with the label
     *
     * @return int[]
     */
    public function getArtists(Label $label): array;

    /**
     * @return array<int, string>
     */
    public function getByAlbum(int $albumId): array;

    /**
     * @return string[]
     */
    public function getByArtist(int $artistId): array;

    /**
     * Reads the labels of one category, together with every label still missing an mbid
     *
     * @return list<int>
     */
    public function getIdsByCategory(string $category): array;

    /**
     * Reads whole label rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $labelIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $labelIds): array;

    public function lookup(string $labelName, int $labelId = 0): int;

    /**
     * Moves every album association from one album onto another
     */
    public function migrateAlbum(int $oldAlbumId, int $newAlbumId): void;

    /**
     * Moves every artist association from one artist onto another
     */
    public function migrateArtist(int $oldArtistId, int $newArtistId): void;

    /**
     * Saves the label, inserting it when it is new
     *
     * Returns the id of a newly created label, null when an existing one was updated
     */
    public function persist(Label $label): ?int;

    public function removeArtistAssoc(int $labelId, int $artistId): void;
}
