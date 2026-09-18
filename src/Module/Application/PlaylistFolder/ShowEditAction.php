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
use Ampache\Gui\Form\PlaylistFolderFormView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\Folder\PlaylistFolderTreeFormatterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shows the rename/move form for one of the current user's folders
 */
final readonly class ShowEditAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_edit';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
        private PlaylistFolderTreeFormatterInterface $treeFormatter,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false) {
            throw new AccessDeniedException();
        }

        $user     = $gatekeeper->getUser();
        $folderId = (int) ($request->getQueryParams()['folder'] ?? 0);
        $folder   = $this->playlistFolderRepository->findById($folderId);
        if ($user === null || !$folder instanceof PlaylistFolder || !$folder->isVisible($user)) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();
        echo new PlaylistFolderFormView(
            $this->configContainer->getWebPath('/client'),
            $folder->getId(),
            $folder->getName(),
            $folder->getParentId(),
            $this->treeFormatter->flatten($user, $folder->getId())
        )->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
