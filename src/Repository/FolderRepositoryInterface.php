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

interface FolderRepositoryInterface
{
    public function findById(?int $folderId = null): ?Folder;

    public function getByName(string $folderName, int $catalogId = 0, ?int $parent = null): ?Folder;

    public function getByPathName(string $folderPath, int $catalogId = 0, ?string $parentPath = null): ?Folder;

    /**
     * Return the list of all available folders
     *
     * @return string[]
     */
    public function getAll(): array;

    public function lookup(string $folderName, int $catalogId = 0, ?int $parent = null): int;

    public function lookupByPathName(string $folderPath, int $catalogId = 0, ?int $parent = null): int;

    public function create(string $folderName, int $catalogId, string $folderPath = '', ?int $parent = null): ?Folder;

    public function delete(int $folderId): void;

    public function add_folder_map(int $object_id, string $object_type, string $dir_path, int $catalog_id): void;

    /**
     * This cleans out unused folders
     */
    public function collectGarbage(): void;
}
