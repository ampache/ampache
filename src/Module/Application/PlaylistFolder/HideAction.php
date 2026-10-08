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
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\RFC\RFC7231;

/**
 * Marks a folder -- the current user's own or one shared with them -- as hidden in their own root
 *
 * It stays listed and usable; the mark is a personal declutter, not a visibility change for anyone else.
 */
final readonly class HideAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'hide';

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

        $user     = $gatekeeper->getUser();
        $folderId = (int) ($request->getQueryParams()['folder'] ?? 0);
        $folder   = $this->playlistFolderRepository->findById($folderId);
        if (
            $user === null
            || !$folder instanceof PlaylistFolder
            || !$this->playlistFolderRepository->isReadableBy($folderId, $user->getId())
        ) {
            throw new AccessDeniedException();
        }

        $this->playlistFolderRepository->hide($user->getId(), $folderId);

        return $this->responseFactory
            ->createResponse(RFC7231::FOUND)
            ->withHeader('Location', $this->configContainer->getWebPath() . '/browse.php?action=playlist_folder');
    }
}
