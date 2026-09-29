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

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Writes a whole new child order for one folder level, as dragged in the interface
 *
 * `order` is a semicolon list of `<type>-<id>` tokens -- a subfolder or a placed playlist/smartlist, in the
 * same encoding `PlaylistFolderAction` builds its rows from. The counterpart of `Collection\SetTrackNumbersAction`.
 */
final readonly class SetSortOrderAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'set_sort_order';

    public function __construct(
        private RequestParserInterface $requestParser,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
            throw new AccessDeniedException('Access Denied: playlist folders are only available to a logged in user.');
        }

        $user = $gatekeeper->getUser();
        if ($user === null) {
            throw new AccessDeniedException('Access Denied: playlist folders are only available to a logged in user.');
        }

        $folderId = (int) $this->requestParser->getFromRequest('folder');
        $folder   = ($folderId > PlaylistFolder::ROOT)
            ? $this->playlistFolderRepository->findById($folderId)
            : null;

        if ($folderId > PlaylistFolder::ROOT && (!$folder instanceof PlaylistFolder || !$folder->isVisible($user))) {
            throw new AccessDeniedException('Access Denied: playlist folder filter');
        }

        $this->ui->showHeader();

        $order = $this->requestParser->getFromRequest('order');
        if ($order !== '') {
            $position = (int) $this->requestParser->getFromRequest('offset') + 1;
            if ($position < 1) {
                $position = 1;
            }

            foreach (explode(';', $order) as $token) {
                if ($token === '' || !preg_match('/^([a-z_]+)-([0-9]+)$/', $token, $matches)) {
                    continue;
                }

                $type     = $matches[1];
                $objectId = (int) $matches[2];

                if ($type === 'playlist_folder') {
                    // A dragged id is only ever one this level's own render put there, but the repository's
                    // `update()` does not itself check ownership, so it is checked here before writing
                    $subfolder = $this->playlistFolderRepository->findById($objectId);
                    if ($subfolder instanceof PlaylistFolder && $subfolder->isVisible($user)) {
                        $this->playlistFolderRepository->update($objectId, sortOrder: $position);
                    }
                } else {
                    $this->playlistFolderRepository->place($user, $objectId, $type, $folderId, $position);
                }

                ++$position;
            }
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
