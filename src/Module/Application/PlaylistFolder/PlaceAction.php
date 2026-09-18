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

namespace Ampache\Module\Application\PlaylistFolder;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\RFC\RFC7231;

/**
 * Files an existing playlist or smartlist into one of the current user's folders, or back to the root
 *
 * Collections are a valid folder member too, but this browse and its move control don't surface them yet.
 */
final readonly class PlaceAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'place';

    private const array PLACEABLE_TYPES = ['playlist', 'search'];

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        if (!check_http_referer()) {
            throw new AccessDeniedException();
        }

        $user = $gatekeeper->getUser();
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false || $user === null) {
            throw new AccessDeniedException();
        }

        $params     = $request->getQueryParams();
        $objectType = (string) ($params['object_type'] ?? '');
        $objectId   = (int) ($params['object_id'] ?? 0);
        $folderId   = (int) ($params['folder'] ?? PlaylistFolder::ROOT);
        $fromId     = (int) ($params['from'] ?? PlaylistFolder::ROOT);

        if (!in_array($objectType, self::PLACEABLE_TYPES, true) || $objectId <= 0) {
            throw new AccessDeniedException();
        }

        if ($folderId > PlaylistFolder::ROOT) {
            $folder = $this->playlistFolderRepository->findById($folderId);
            if (!$folder instanceof PlaylistFolder || !$folder->isVisible($user)) {
                throw new AccessDeniedException();
            }

            $this->playlistFolderRepository->place($user, $objectId, $objectType, $folderId);
        } else {
            $this->playlistFolderRepository->unplace($user, $objectId, $objectType);
        }

        return $this->responseFactory
            ->createResponse(RFC7231::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/browse.php?action=playlist_folder%s',
                    $this->configContainer->getWebPath(),
                    ($fromId > PlaylistFolder::ROOT) ? '&folder=' . $fromId : ''
                )
            );
    }
}
