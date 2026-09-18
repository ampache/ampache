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
use Ampache\Module\Playlist\Folder\PlaylistFolderItemsLoaderInterface;
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

    private PlaylistFolderItemsLoaderInterface $itemsLoader;
    private PlaylistFolderRepositoryInterface $playlistFolderRepository;

    public function __construct(
        PlaylistFolderRepositoryInterface $playlistFolderRepository,
        PlaylistFolderItemsLoaderInterface $itemsLoader,
    ) {
        $this->playlistFolderRepository = $playlistFolderRepository;
        $this->itemsLoader              = $itemsLoader;
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
        $items  = $this->itemsLoader->getItems($user, $folder);

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
}
