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

use Ampache\Repository\Model\MoodCountTypeEnum;

/**
 * Manages database access related to moods
 *
 * Tables: `mood`, `mood_map`
 */
interface MoodRepositoryInterface
{
    /**
     * Maps a mood onto an object
     */
    public function addMap(int $moodId, string $objectType, int $objectId, int $userId): int;

    /**
     * Drops the maps of objects that no longer exist, then the moods nothing points at any more
     */
    public function collectGarbage(): void;

    /**
     * Creates a mood by name and returns its id
     */
    public function create(string $name): int;

    /**
     * Steps the per-type counter down, never below zero
     */
    public function decrementCount(int $moodId, MoodCountTypeEnum $type): void;

    /**
     * Removes a mood and every map pointing at it
     */
    public function delete(int $moodId): void;

    /**
     * Reads the id of the mood with this name, null when there is none
     */
    public function findIdByName(string $name): ?int;

    /**
     * Reads the ids of the objects carrying one mood, for browsing it
     *
     * @return list<int>
     */
    public function getMoodObjectIds(string $objectType, int $moodId, int $count, int $offset, int $catalogId): array;

    /**
     * Reads every mood, optionally limited to those counting against one object type
     *
     * @return list<array{id: int, name: string, count: int}>
     */
    public function getMoods(?string $type, int $limit, string $order): array;

    /**
     * Reads the moods mapped onto one object, or onto every object of a type when no id is given
     *
     * @return list<array{id: int, name: string, user: int}>
     */
    public function getObjectMoods(string $objectType, ?int $objectId): array;

    /**
     * Reads the mood rows for a set of ids, for the request cache
     *
     * @param list<int> $moodIds
     *
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $moodIds): array;

    /**
     * Reads the distinct moods tagged on the songs of one album
     *
     * @return list<string>
     */
    public function getSongMoodNamesByAlbum(int $albumId): array;

    /**
     * Reads the distinct moods tagged on the songs one artist is mapped onto
     *
     * @return list<string>
     */
    public function getSongMoodNamesByArtist(int $artistId): array;

    /**
     * Reads the moods mapped onto one object, heaviest first
     *
     * `user` is the owner of the map: 0 when the mood came from the file tags, otherwise whoever set it by hand.
     *
     * @return list<array{id: int, name: string, user: int, count: int}>
     */
    public function getTopMoods(string $objectType, int $objectId, int $limit): array;

    /**
     * Steps the per-type counter up
     */
    public function incrementCount(int $moodId, MoodCountTypeEnum $type): void;

    /**
     * Whether this object already carries this mood for this user
     */
    public function mapExists(string $objectType, int $objectId, int $moodId, int $userId): bool;

    /**
     * Moves the maps of one object onto another, for a merge
     */
    public function migrateMaps(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Rebuilds one type's counters from the maps that exist
     */
    public function recountType(MoodCountTypeEnum $type): void;

    /**
     * Drops every mood mapped onto an object
     */
    public function removeAllMaps(string $objectType, int $objectId, ?int $userId = null): void;

    /**
     * Drops one mood from one object
     */
    public function removeMap(int $moodId, string $objectType, int $objectId, ?int $userId = null): void;

    /**
     * Renames a mood
     */
    public function rename(int $moodId, string $name): void;

    /**
     * Zeroes the counter of every mood that has no map of this type left
     */
    public function zeroUnmappedType(MoodCountTypeEnum $type): void;
}
