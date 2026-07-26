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

use Ampache\Repository\Model\TagCountTypeEnum;

interface TagRepositoryInterface
{
    /**
     * Maps a tag onto an object; duplicates are ignored, and the new map id is returned
     */
    public function addMap(int $tagId, string $objectType, int $objectId, int $userId): int;

    /**
     * Drops orphaned maps and empty tags, then recomputes every counter column from what is left
     */
    public function collectGarbage(): void;

    /**
     * Inserts a tag by name, replacing any existing row with the same name, and returns its id
     */
    public function create(string $name): int;

    /**
     * Steps a counter column down, without letting it go negative
     */
    public function decrementCount(int $tagId, TagCountTypeEnum $type): void;

    /**
     * Deletes a tag along with its maps and its merges
     */
    public function delete(int $tagId): void;

    /**
     * Finds a tag by exact name; names are unique, so this is the identity lookup
     */
    public function findIdByName(string $name): ?int;

    /**
     * Reads the tag map rows for a set of objects, for the prefetch that feeds the browse display
     *
     * @param array<int|string> $objectIds
     *
     * @return list<array{id: int, tag_id: int, name: string, object_id: int, user: int}>
     */
    public function getMapRows(string $objectType, array $objectIds): array;

    /**
     * Counts the distinct tags that have been merged into another one
     */
    public function getMergedCount(): int;

    /**
     * Reads the names a tag has been merged into, used to decide whether a rename already covers a tag
     *
     * @return list<string>
     */
    public function getMergedNames(int $tagId): array;

    /**
     * Reads the tags this one has been merged into
     *
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function getMergedTags(int $tagId): array;

    /**
     * Reads every visible tag applied to one object type, optionally narrowed to a single object
     *
     * @return list<array{id: int, name: string, is_hidden: int, user: int}>
     */
    public function getObjectTags(string $objectType, ?int $objectId): array;

    /**
     * Reads whole tag rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $tagIds
     *
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $tagIds): array;

    /**
     * Reads the ids of tags applied to a given object type
     *
     * @return list<int>
     */
    public function getTagIds(string $objectType, int $count, int $offset): array;

    /**
     * Reads the ids of objects carrying a given tag, or every tagged object when the tag id is 0
     *
     * @return list<int>
     */
    public function getTagObjects(string $objectType, int $tagId, int $count, int $offset, int $catalogId): array;

    /**
     * Reads the tag cloud: every tag with a usage count, filtered and ordered as the caller asked
     *
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function getTags(?string $type, int $limit, string $order): array;

    /**
     * Reads the highest-weighted tags applied to a single object
     *
     * @return list<array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function getTopTags(string $objectType, int $objectId, int $limit): array;

    /**
     * Steps a counter column up
     */
    public function incrementCount(int $tagId, TagCountTypeEnum $type): void;

    /**
     * Reports whether the object already carries this tag, counting anything merged into it as a match
     */
    public function mapExists(string $objectType, int $objectId, int $tagId, int $userId): bool;

    /**
     * Copies a tag's maps onto another tag, skipping the ones the target already has
     */
    public function mergeInto(int $tagId, int $mergeToId): void;

    /**
     * Repoints a tag map from one object onto another
     */
    public function migrateMaps(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Records the merge so future tagging follows it, rather than only moving the existing maps
     */
    public function persistMerge(int $tagId, int $mergeToId): void;

    /**
     * Recomputes one counter column across every tag that still has a map of that type
     */
    public function recountType(TagCountTypeEnum $type): void;

    /**
     * Drops every tag map on an object
     */
    public function removeAllMaps(string $objectType, int $objectId): void;

    /**
     * Drops one tag map from an object
     */
    public function removeMap(int $tagId, string $objectType, int $objectId, int $userId): void;

    /**
     * Drops every map a tag holds, leaving the tag row itself in place
     */
    public function removeMapsForTag(int $tagId): void;

    /**
     * Drops the merges recorded against a tag, so it stops absorbing new tagging
     */
    public function removeMerges(int $tagId): void;

    /**
     * Renames a tag
     */
    public function rename(int $tagId, string $name): void;

    /**
     * Hides or unhides a tag; hiding it also zeroes the counters, because it no longer applies to anything
     */
    public function setHidden(int $tagId, int $isHidden, bool $resetCounts): void;

    /**
     * Zeroes one counter column on the tags recountType() cannot reach, having no map of that type left at all
     */
    public function zeroUnmappedType(TagCountTypeEnum $type): void;
}
