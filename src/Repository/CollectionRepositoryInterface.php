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

use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;

interface CollectionRepositoryInterface
{
    /**
     * Append one object to the end of the collection
     *
     * Pass $unique to refuse an object that is already a member; without it a collection may hold duplicates,
     * matching how the `unique_playlist` preference governs playlists.
     *
     * @return bool false when $unique refused the add
     */
    public function addItem(int $collectionId, int $objectId, string $objectType, bool $unique = false): bool;

    /**
     * Remove members whose object no longer exists, and collections whose owner is gone
     */
    public function collectGarbage(): void;

    public function create(
        string $name,
        User $user,
        string $type = 'private',
        ?string $objectType = null,
    ): ?int;

    public function delete(int $collectionId): void;

    public function findById(int $collectionId): ?Collection;

    /**
     * Collections the user is allowed to see: their own, plus every public one
     *
     * @return list<int>
     */
    public function getByUser(User $user, ?string $objectType = null): array;

    public function getItemCount(int $collectionId): int;

    /**
     * Members of a collection, in curated order
     *
     * @return list<array{'id': int, 'object_id': int, 'object_type': string, 'track': int}>
     */
    public function getItems(int $collectionId): array;

    /**
     * The distinct object types a collection currently holds
     *
     * @return list<string>
     */
    public function getItemTypes(int $collectionId): array;

    /**
     * The highest position currently used, so an appended member carries on from there
     */
    public function getLastTrackNumber(int $collectionId): int;

    /**
     * Reads whole collection rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $collectionIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $collectionIds): array;

    /**
     * Entry ids in their stored order, for renumbering
     *
     * @return list<int>
     */
    public function getTrackIdsInOrder(int $collectionId): array;

    /**
     * Whether this exact object is already a member
     */
    public function hasItem(int $collectionId, int $objectId, string $objectType): bool;

    /**
     * Whether the object a caller wants to curate is really in its own table
     */
    public function objectExists(string $objectType, int $objectId): bool;

    /**
     * Renumber every member from 1 so positions stay dense
     */
    public function regenerateTrackNumbers(int $collectionId): void;

    public function removeItem(int $collectionId, int $objectId, string $objectType): void;

    /**
     * Remove one member by the id of its `collection_map` row
     */
    public function removeItemById(int $collectionId, int $mapId): void;

    /**
     * Remove the single member holding one position
     */
    public function removeItemByTrack(int $collectionId, int $track): void;

    /**
     * Drop whatever sits at $track and put this object there instead
     */
    public function replaceTrackAtNumber(int $collectionId, int $objectId, string $objectType, int $track): void;

    /**
     * Store the position of one member, addressed by its `collection_map` row
     */
    public function setTrackNumber(int $mapId, int $track): void;

    public function update(
        int $collectionId,
        ?string $name = null,
        ?string $type = null,
        ?string $objectType = null,
        ?string $collaborate = null,
    ): void;
}
