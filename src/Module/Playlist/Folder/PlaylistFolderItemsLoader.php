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

use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;

/**
 * Shared by the API and the web browse, so what "the root" means never drifts between them.
 */
final readonly class PlaylistFolderItemsLoader implements PlaylistFolderItemsLoaderInterface
{
    public function __construct(
        private BrowseFactoryInterface $browseFactory,
        private CollectionRepositoryInterface $collectionRepository,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
    ) {}

    public function getItems(User $user, ?PlaylistFolder $folder): array
    {
        return ($folder === null)
            ? $this->rootItems($user)
            : $this->playlistFolderRepository->getPlacements($user, $folder->getId());
    }

    /**
     * Every list the user can see that is not filed in a folder
     *
     * Visibility is taken from the existing browses and repository rather than re-derived here, so public,
     * owned, collaborated and shared lists stay in step with the rest of the app.
     *
     * @return list<array{object_id: int, object_type: string, sort_order: int}>
     */
    private function rootItems(User $user): array
    {
        $placements = $this->playlistFolderRepository->getPlacementMap($user);

        $items = [];
        foreach ($this->visibleLists($user) as $entry) {
            $key       = sprintf('%s-%d', $entry['object_type'], $entry['object_id']);
            $placement = $placements[$key] ?? null;

            // Filed in a real folder, so it is not at the root
            if ($placement !== null && $placement['folder'] !== PlaylistFolder::ROOT) {
                continue;
            }

            $items[] = [
                'object_id' => $entry['object_id'],
                'object_type' => $entry['object_type'],
                'sort_order' => $placement['sort_order'] ?? 0,
            ];
        }

        usort(
            $items,
            static fn(array $left, array $right): int => [$left['sort_order'], $left['object_type'], $left['object_id']]
                <=> [$right['sort_order'], $right['object_type'], $right['object_id']]
        );

        return $items;
    }

    /**
     * Playlists, smartlists and collections the user may see, in the table spelling of their type
     *
     * @return list<array{object_id: int, object_type: string}>
     */
    private function visibleLists(User $user): array
    {
        $browse = $this->browseFactory->create(null, false);
        $browse->set_user_id($user);
        $browse->set_type('playlist_search');
        $browse->set_sort('name', 'ASC', false);
        $browse->set_filter('playlist_open', $user->getId());

        $entries = [];
        foreach ($browse->get_objects() as $listId) {
            // The browse merges both kinds, marking a smartlist by prefixing its id
            $entries[] = ((int) $listId === 0)
                ? ['object_id' => (int) str_replace('smart_', '', (string) $listId), 'object_type' => 'search']
                : ['object_id' => (int) $listId, 'object_type' => 'playlist'];
        }

        foreach ($this->collectionRepository->getByUser($user) as $collectionId) {
            $entries[] = ['object_id' => $collectionId, 'object_type' => 'collection'];
        }

        return $entries;
    }
}
