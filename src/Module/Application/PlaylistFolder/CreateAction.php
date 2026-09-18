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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\PlaylistFolderFormView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\Folder\PlaylistFolderTreeFormatterInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Creates a folder in the current user's playlist folder tree
 */
final readonly class CreateAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'create';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
        private PlaylistFolderTreeFormatterInterface $treeFormatter,
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user = $gatekeeper->getUser();
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
            || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
            || $user === null
            || !$this->requestParser->verifyForm('add_playlist_folder')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $name = $this->requestParser->getFromRequest('name');
        if (!PlaylistFolder::isValidName($name)) {
            AmpError::add('name', T_('Name is required'));
        }

        $parentId = (int) $this->requestParser->getFromRequest('parent');

        if (AmpError::occurred()) {
            echo $this->formView($user, $name, $parentId)->render();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $folderId = $this->playlistFolderRepository->create($user, $name, $parentId);
        if ($folderId === null) {
            AmpError::add('name', T_('That name already exists'));
            echo $this->formView($user, $name, $parentId)->render();
        } else {
            $this->ui->showConfirmation(
                T_('Folder created'),
                $name,
                sprintf(
                    '%s/browse.php?action=playlist_folder%s',
                    $this->configContainer->getWebPath('/client'),
                    ($parentId > PlaylistFolder::ROOT) ? '&folder=' . $parentId : ''
                )
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    private function formView(User $user, string $name, int $parentId): PlaylistFolderFormView
    {
        return new PlaylistFolderFormView(
            $this->configContainer->getWebPath('/client'),
            null,
            $name,
            $parentId,
            $this->treeFormatter->flatten($user)
        );
    }
}
