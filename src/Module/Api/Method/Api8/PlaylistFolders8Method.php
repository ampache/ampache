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
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the calling user's playlist folder tree, plus other users' shared top-level folders
 *
 * Only api version 8 knows about playlist folders.
 */
final class PlaylistFolders8Method implements MethodInterface
{
    public const string ACTION = 'playlist_folders';

    private PlaylistFolderRepositoryInterface $playlistFolderRepository;

    public function __construct(
        PlaylistFolderRepositoryInterface $playlistFolderRepository,
    ) {
        $this->playlistFolderRepository = $playlistFolderRepository;
    }

    /**
     * playlist_folders
     * MINIMUM_API_VERSION=800000
     *
     * The whole tree as a flat list, plus other users' shared top-level folders appended after it; clients
     * rebuild the hierarchy from each folder's `parent` and tell the two apart by `hidden`/ownership.
     *
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $folders = array_merge(
            $this->playlistFolderRepository->getTree($user),
            $this->playlistFolderRepository->getPublicRootFolders($user->getId())
        );
        if ($folders === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'playlist_folder')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->playlistFolders($apiVersion, $folders, $user)
        );

        return $response;
    }
}
