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
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns a list to the root by removing its placement
 *
 * Only api version 8 knows about playlist folders.
 */
final class PlaylistFolderRemove8Method implements MethodInterface
{
    use PlaylistFolderLoaderTrait;

    public const string ACTION = 'playlist_folder_remove';

    public const string REST_ACTION = 'playlist_folder_items_delete';

    private PlaylistFolderRepositoryInterface $playlistFolderRepository;

    public function __construct(
        PlaylistFolderRepositoryInterface $playlistFolderRepository,
    ) {
        $this->playlistFolderRepository = $playlistFolderRepository;
    }

    /**
     * playlist_folder_remove
     * MINIMUM_API_VERSION=800000
     *
     * Take a list out of its folder. The list itself is untouched and reappears at the root, because an
     * unfiled list has no placement row at all.
     *
     * id   = (integer) UID of the list
     * type = (string) 'playlist'|'smartlist'|'collection'
     *
     * @param array{
     *     filter?: string,
     *     id?: int,
     *     type?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['id', 'type'] as $required) {
            if (!array_key_exists($required, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $required)
                );
            }
        }

        $objectType = PlaylistFolder::normalizeType((string) $input['type']);
        if (!PlaylistFolder::isValidType($objectType)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'type')
            );
        }

        $this->playlistFolderRepository->unplace($user, (int) $input['id'], $objectType);

        $response->getBody()->write(
            $output->success($apiVersion, 'playlist folder updated')
        );

        return $response;
    }
}
