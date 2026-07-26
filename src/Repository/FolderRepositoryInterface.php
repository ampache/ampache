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

use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;

interface FolderRepositoryInterface
{
    /**
     * This cleans out unused folders
     */
    public function collectGarbage(): void;

    public function create(string $folderName, int $catalogId, string $folderPath = '', ?int $parent_id = null): ?Folder;

    public function delete(int $folderId): void;

    public function findById(?int $folderId = null): ?Folder;

    /**
     * Return the list of all available folders
     *
     * @return string[]
     */
    public function getAll(): array;

    public function getByName(string $folderName, ?int $catalogId = null, ?int $parent = null): Folder|Podcast_Episode|Song|Video|null;

    public function getByPathName(string $folderPath, int $catalogId = 0, ?string $parentPath = null): ?Folder;

    /**
     * Returns the direct children of a folder. Pass null for the virtual root.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function getChildren(?int $folderId): array;

    /**
     * Return the number of entries in the database...
     */
    public function getItemCount(): int;

    /**
     * Returns everything below a folder, optionally narrowed to a single type
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function getMedias(Folder $folder, ?string $filterType = null): array;

    /**
     * Returns a folder's own name, null when there is no such folder
     */
    public function getNameById(int $folderId): ?string;

    /**
     * Returns the contents of a folder. Pass null for the virtual root.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function getObjects(?int $folderId): array;

    /**
     * Whether the folder has any mapped children
     */
    public function hasChildren(int $folderId): bool;

    public function lookup(string $folderName, int $catalogId = 0, ?int $parent_id = null): int;

    public function lookupByPathName(string $folderPath, int $catalogId = 0): int;

    /**
     * Moves every folder_map row of the given type from one object onto another
     */
    public function migrateObject(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Saves the folder, inserting it when it is new
     *
     * Returns the id of a newly created folder, null when an existing one was updated
     */
    public function persist(Folder $folder): ?int;

    /**
     * Update folder counts columns after large actions
     */
    public function update_folder_counts(): void;

    /**
     * Update mapping table after large actions
     */
    public function update_folder_map(): void;

    public function update_utime(int $folder_id, int $time = 0): void;
}
