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

namespace Ampache\Module\Playlist\Folder;

use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;

final readonly class PlaylistFolderRowIds implements PlaylistFolderRowIdsInterface
{
    private const string TYPE_FOLDER = 'playlist_folder';

    public function __construct(
        private PlaylistFolderItemsLoaderInterface $itemsLoader,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
    ) {}

    public function getRowIds(User $user, ?PlaylistFolder $folder): array
    {
        $entries = [];

        if ($folder === null) {
            foreach ($this->playlistFolderRepository->getChildren($user, PlaylistFolder::ROOT) as $child) {
                $entries[] = ['type' => self::TYPE_FOLDER, 'id' => $child->getId(), 'sort_order' => $child->getSortOrder()];
            }

            // other users' shared top-level folders, appended after the viewer's own
            $shared = $this->playlistFolderRepository->getPublicRootFolders($user->getId());
            usort(
                $shared,
                static fn(PlaylistFolder $a, PlaylistFolder $b): int => [$a->getUserId(), $a->getSortOrder()] <=> [$b->getUserId(), $b->getSortOrder()]
            );
            foreach ($shared as $index => $child) {
                $entries[] = ['type' => self::TYPE_FOLDER, 'id' => $child->getId(), 'sort_order' => PHP_INT_MAX - count($shared) + $index];
            }
        } elseif ($folder->isVisible($user)) {
            foreach ($this->playlistFolderRepository->getChildren($user, $folder->getId()) as $child) {
                $entries[] = ['type' => self::TYPE_FOLDER, 'id' => $child->getId(), 'sort_order' => $child->getSortOrder()];
            }
        } else {
            foreach ($this->playlistFolderRepository->getPublicChildren($folder->getUserId(), $folder->getId()) as $child) {
                $entries[] = ['type' => self::TYPE_FOLDER, 'id' => $child->getId(), 'sort_order' => $child->getSortOrder()];
            }
        }

        foreach ($this->itemsLoader->getItems($user, $folder) as $item) {
            // collections are a valid folder member, but this browse does not surface them yet
            if ($item['object_type'] === 'collection') {
                continue;
            }

            $entries[] = ['type' => $item['object_type'], 'id' => $item['object_id'], 'sort_order' => $item['sort_order']];
        }

        // Stable since PHP 8: a tie -- both still at the default 0 until something is dragged -- keeps each
        // side's own `getChildren()`/`getPlacements()` order rather than shuffling them against each other
        usort($entries, static fn(array $left, array $right): int => $left['sort_order'] <=> $right['sort_order']);

        return array_map(
            static fn(array $entry): string => sprintf('%s-%d', $entry['type'], $entry['id']),
            $entries
        );
    }
}
