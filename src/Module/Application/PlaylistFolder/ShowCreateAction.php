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
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shows the create-a-playlist-folder form
 */
final readonly class ShowCreateAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_create';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PlaylistFolderTreeFormatterInterface $treeFormatter,
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false) {
            throw new AccessDeniedException();
        }

        $user = $gatekeeper->getUser();
        if ($user === null) {
            throw new AccessDeniedException();
        }

        $parentId = (int) ($request->getQueryParams()['folder'] ?? PlaylistFolder::ROOT);

        $this->ui->showHeader();
        echo new PlaylistFolderFormView(
            $this->configContainer->getWebPath(),
            null,
            $this->requestParser->getFromRequest('name'),
            $parentId,
            $this->treeFormatter->flatten($user)
        )->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
