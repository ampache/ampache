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
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates a playlist folder
 *
 * Only api version 8 knows about playlist folders.
 */
final class PlaylistFolderCreate8Method implements MethodInterface
{
    use PlaylistFolderLoaderTrait;

    public const string ACTION = 'playlist_folder_create';

    public const string REST_ACTION = 'playlist_folders_create';

    private PlaylistFolderRepositoryInterface $playlistFolderRepository;

    public function __construct(
        PlaylistFolderRepositoryInterface $playlistFolderRepository,
    ) {
        $this->playlistFolderRepository = $playlistFolderRepository;
    }

    /**
     * playlist_folder_create
     * MINIMUM_API_VERSION=800000
     *
     * Create a folder in the calling user's tree
     *
     * name       = (string) folder name; may not contain a / and must be unique among its siblings
     * parent     = (string) parent folder as an id or a name path //optional, root when omitted
     * sort_order = (integer) position among its siblings //optional, appended when omitted
     *
     * @param array{
     *     name?: string,
     *     parent?: string,
     *     sort_order?: int,
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
        if (!array_key_exists('name', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'name')
            );
        }

        $parentId  = $this->resolveParentId($input, $user);
        $sortOrder = (isset($input['sort_order'])) ? (int) $input['sort_order'] : null;

        $folderId = $this->playlistFolderRepository->create($user, (string) $input['name'], $parentId, $sortOrder);
        if ($folderId === null) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'name'
                )
            );

            return $response;
        }

        $folder = $this->playlistFolderRepository->findById($folderId);

        $response->getBody()->write(
            $output->playlistFolders($apiVersion, ($folder === null) ? [] : [$folder], $user)
        );

        return $response;
    }
}
