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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the lists filed in a playlist folder
 *
 * The root is not a stored folder: it holds every list the user can see that has not been filed elsewhere,
 * so a list appears there without anything ever having been written for it.
 *
 * Only api version 8 knows about playlist folders.
 */
final class PlaylistFolderItems8Method implements MethodInterface
{
    use PlaylistFolderLoaderTrait;

    public const string ACTION = 'playlist_folder_items';

    private BrowseFactoryInterface $browseFactory;
    private CollectionRepositoryInterface $collectionRepository;
    private PlaylistFolderRepositoryInterface $playlistFolderRepository;

    public function __construct(
        PlaylistFolderRepositoryInterface $playlistFolderRepository,
        CollectionRepositoryInterface $collectionRepository,
        BrowseFactoryInterface $browseFactory,
    ) {
        $this->playlistFolderRepository = $playlistFolderRepository;
        $this->collectionRepository     = $collectionRepository;
        $this->browseFactory            = $browseFactory;
    }

    /**
     * playlist_folder_items
     * MINIMUM_API_VERSION=800000
     *
     * The playlists, smartlists and collections filed in one folder
     *
     * filter = (string) the folder, as an id or a name path; 0 or / for the root //optional, root when omitted
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $folder = $this->loadFolderOrRoot($input, $user);

        $items = ($folder === null)
            ? $this->rootItems($user)
            : $this->playlistFolderRepository->getPlacements($user, $folder->getId());

        if ($items === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'playlist_folder')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->playlistFolderItems($apiVersion, $folder, $items, $user, $input['auth'])
        );

        return $response;
    }

    /**
     * Every list the user can see that is not filed in a folder
     *
     * Visibility is taken from the existing browses and repository rather than re-derived here, so public,
     * owned, collaborated and shared lists stay in step with the rest of the API.
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
