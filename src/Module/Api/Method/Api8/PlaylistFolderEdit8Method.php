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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Renames, re-parents, repositions or (un)hides a playlist folder
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
     * Change a folder's name, parent, position or visibility to the caller. Anything not sent is left as
     * it is. `hidden` is the one field a non-owner may also send, for a folder shared with them.
     *
     * filter     = (string) the folder, as an id or a name path
     * name       = (string) new name //optional
     * parent     = (string) new parent as an id or a name path, or 0 for the root //optional
     * sort_order = (integer) new position among its siblings //optional
     * type       = (string) 'private', 'public' //optional, public requires Content Manager
     * hidden     = (boolean) true to mark the folder hidden in the caller's own root, false to unhide //optional
     *
     * @param array{
     *     filter?: string,
     *     name?: string,
     *     parent?: string,
     *     sort_order?: int,
     *     type?: string,
     *     hidden?: bool,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws AccessFailedException
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
        // Own or another user's alike -- only `hidden` may be set on a folder the caller does not own
        $folder  = $this->loadAnyFolder($input, $user);
        $isOwner = $folder->isVisible($user);

        $name      = (isset($input['name'])) ? (string) $input['name'] : null;
        $parentId  = (array_key_exists('parent', $input)) ? $this->resolveParentId($input, $user) : null;
        $sortOrder = (isset($input['sort_order'])) ? (int) $input['sort_order'] : null;
        $type      = (isset($input['type'])) ? (((string) $input['type'] === 'public') ? 'public' : 'private') : null;
        $hidden    = (array_key_exists('hidden', $input)) ? make_bool($input['hidden']) : null;

        if (!$isOwner && ($name !== null || $parentId !== null || $sortOrder !== null || $type !== null)) {
            throw new AccessFailedException('Require: folder ownership');
        }

        if ($type === 'public' && $user->access < AccessLevelEnum::CONTENT_MANAGER->value) {
            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::ACCESS_DENIED, 'Access Denied', self::ACTION, 'type')
            );

            return $response;
        }

        if ($name === null && $parentId === null && $sortOrder === null && $type === null && $hidden === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'name, parent, sort_order, type or hidden')
            );
        }

        if ($hidden !== null) {
            if ($hidden) {
                $this->playlistFolderRepository->hide($user->getId(), $folder->getId());
            } else {
                $this->playlistFolderRepository->unhide($user->getId(), $folder->getId());
            }
        }

        // A refusal here is a name a sibling holds or a move into the folder's own subtree
        $hasFieldUpdate = ($name !== null || $parentId !== null || $sortOrder !== null || $type !== null);
        if ($hasFieldUpdate && !$this->playlistFolderRepository->update($folder->getId(), $name, $parentId, $sortOrder, $type)) {
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
