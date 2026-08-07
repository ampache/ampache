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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;

/**
 * Resolving the playlist folder named by `filter`, shared by every playlist folder method.
 */
trait PlaylistFolderLoaderTrait
{
    /**
     * A filter made up of digits alone is a folder id; a name path always carries its separators
     */
    private function isObjectId(string $filter): bool
    {
        return (bool) preg_match('/^-?[0-9]+$/', $filter);
    }

    /**
     * Whether the request named the root rather than a stored folder
     */
    private function isRoot(string $filter): bool
    {
        return $filter === '' || $filter === PlaylistFolder::PATH_SEPARATOR || $filter === (string) PlaylistFolder::ROOT;
    }

    /**
     * The folder named by `filter`, addressed by id or by name path.
     *
     * Another user's folder reports not found rather than denied, so a tree cannot be probed from outside.
     *
     * @param array<string, mixed> $input
     *
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    private function loadFolder(array $input, User $user): PlaylistFolder
    {
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $filter = (string) $input['filter'];
        $folder = ($this->isObjectId($filter))
            ? $this->playlistFolderRepository->findById((int) $filter)
            : $this->playlistFolderRepository->findByPath($user, $filter);

        if (
            $folder === null
            || !$folder->isVisible($user)
        ) {
            throw new ResultEmptyException($filter);
        }

        return $folder;
    }

    /**
     * The folder named by `filter`, where the root is a legitimate answer rather than a missing one.
     *
     * Returns null for the root, which has no row of its own.
     *
     * @param array<string, mixed> $input
     *
     * @throws ResultEmptyException
     */
    private function loadFolderOrRoot(array $input, User $user): ?PlaylistFolder
    {
        $filter = (string) ($input['filter'] ?? PlaylistFolder::ROOT);
        if ($this->isRoot($filter)) {
            return null;
        }

        return $this->loadFolder(['filter' => $filter], $user);
    }

    /**
     * A parent named by id or name path, defaulting to the root when the request does not say.
     *
     * @param array<string, mixed> $input
     *
     * @throws RequestParamMissingException
     */
    private function resolveParentId(array $input, User $user): int
    {
        $parent = (string) ($input['parent'] ?? PlaylistFolder::ROOT);
        if ($this->isRoot($parent)) {
            return PlaylistFolder::ROOT;
        }

        $folder = ($this->isObjectId($parent))
            ? $this->playlistFolderRepository->findById((int) $parent)
            : $this->playlistFolderRepository->findByPath($user, $parent);

        if (
            $folder === null
            || !$folder->isVisible($user)
        ) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'parent')
            );
        }

        return $folder->getId();
    }
}
