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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Renames, re-parents or repositions a playlist folder
 *
 * Only api version 8 knows about playlist folders.
 */
final class PlaylistFolderEdit8Method implements MethodInterface
{
    use PlaylistFolderLoaderTrait;

    public const string ACTION = 'playlist_folder_edit';

    private PlaylistFolderRepositoryInterface $playlistFolderRepository;

    public function __construct(
        PlaylistFolderRepositoryInterface $playlistFolderRepository,
    ) {
        $this->playlistFolderRepository = $playlistFolderRepository;
    }

    /**
     * playlist_folder_edit
     * MINIMUM_API_VERSION=800000
     *
     * Change a folder's name, parent or position. Anything not sent is left as it is.
     *
     * filter     = (string) the folder, as an id or a name path
     * name       = (string) new name //optional
     * parent     = (string) new parent as an id or a name path, or 0 for the root //optional
     * sort_order = (integer) new position among its siblings //optional
     *
     * @param array{
     *     filter?: string,
     *     name?: string,
     *     parent?: string,
     *     sort_order?: int,
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
        $folder = $this->loadFolder($input, $user);

        $name      = (isset($input['name'])) ? (string) $input['name'] : null;
        $parentId  = (array_key_exists('parent', $input)) ? $this->resolveParentId($input, $user) : null;
        $sortOrder = (isset($input['sort_order'])) ? (int) $input['sort_order'] : null;

        if ($name === null && $parentId === null && $sortOrder === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'name, parent or sort_order')
            );
        }

        // A refusal here is a name a sibling holds or a move into the folder's own subtree
        if (!$this->playlistFolderRepository->update($folder->getId(), $name, $parentId, $sortOrder)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'input'
                )
            );

            return $response;
        }

        $updated = $this->playlistFolderRepository->findById($folder->getId());

        $response->getBody()->write(
            $output->playlistFolders($apiVersion, ($updated === null) ? [] : [$updated], $user)
        );

        return $response;
    }
}
