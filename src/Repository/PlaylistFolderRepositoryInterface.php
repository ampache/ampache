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

use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;

interface PlaylistFolderRepositoryInterface
{
    public function collectGarbage(): void;

    /**
     * Create a folder, returning its id, or null when the name is unusable or already taken by a sibling
     */
    public function create(User $user, string $name, int $parentId = PlaylistFolder::ROOT, ?int $sortOrder = null): ?int;

    /**
     * Remove an empty folder; a folder holding child folders or placements is left alone
     */
    public function delete(int $folderId): bool;

    public function findById(int $folderId): ?PlaylistFolder;

    /**
     * Resolve a folder from a name path such as `/Rock/Live`, walking one segment at a time
     */
    public function findByPath(User $user, string $path): ?PlaylistFolder;

    /**
     * The folders directly below a parent, in display order
     *
     * @return list<PlaylistFolder>
     */
    public function getChildren(User $user, int $parentId = PlaylistFolder::ROOT): array;

    /**
     * How many lists sit in each of this user's folders, keyed by folder id
     *
     * Returned for the whole tree at once so a listing does not run one count per folder.
     *
     * @return array<int, int>
     */
    public function getItemCounts(User $user): array;

    /**
     * Ids this user has filed into a real folder, so a root listing can subtract them
     *
     * @return list<int>
     */
    public function getPlacedObjectIds(User $user, string $objectType): array;

    /**
     * Where one list sits for this user, or null when it has never been filed
     *
     * @return array{folder: int, sort_order: int}|null
     */
    public function getPlacement(User $user, int $objectId, string $objectType): ?array;

    /**
     * Every placement this user has made, keyed `objectType-objectId`
     *
     * Read in one query so a list response can carry each item's folder without a lookup per row.
     *
     * @return array<string, array{folder: int, sort_order: int}>
     */
    public function getPlacementMap(User $user): array;

    /**
     * The lists filed directly in one folder, in display order
     *
     * @return list<array{object_id: int, object_type: string, sort_order: int}>
     */
    public function getPlacements(User $user, int $folderId): array;

    /**
     * Every folder in this user's tree, flat and ordered; callers rebuild the hierarchy from `parent`
     *
     * @return list<PlaylistFolder>
     */
    public function getTree(User $user): array;

    /**
     * Whether the folder holds neither a child folder nor a placement
     */
    public function isEmpty(int $folderId): bool;

    /**
     * Write a new folder and return its id, or null when it could not be stored
     */
    public function persist(PlaylistFolder $folder): ?int;

    /**
     * File a list for this user, moving it when it is already filed
     *
     * A null folder means the root. A list at the root with no explicit order is stored as the absence of a
     * row, so that root membership costs nothing for the lists nobody has organised.
     */
    public function place(User $user, int $objectId, string $objectType, ?int $folderId, ?int $sortOrder = null): bool;

    /**
     * Remove this user's placement for one list, returning it to the root
     */
    public function unplace(User $user, int $objectId, string $objectType): void;

    /**
     * Change a folder's name, parent or position; null leaves a field alone
     */
    public function update(int $folderId, ?string $name = null, ?int $parentId = null, ?int $sortOrder = null): bool;

    /**
     * Whether making $newParentId the parent of $folderId would put the folder inside its own subtree
     */
    public function wouldCycle(int $folderId, int $newParentId): bool;
}
