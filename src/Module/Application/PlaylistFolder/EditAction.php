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
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renames and/or re-parents one of the current user's folders
 */
final readonly class EditAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'edit';

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
            || $user === null
            || !$this->requestParser->verifyForm('edit_playlist_folder')
        ) {
            throw new AccessDeniedException();
        }

        $folderId = (int) $this->requestParser->getFromRequest('folder');
        $folder   = $this->playlistFolderRepository->findById($folderId);
        if (!$folder instanceof PlaylistFolder || !$folder->isVisible($user)) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $name = $this->requestParser->getFromRequest('name');
        if (!PlaylistFolder::isValidName($name)) {
            AmpError::add('name', T_('Name is required'));
        }

        $parentId = (int) $this->requestParser->getFromRequest('parent');
        // Checked ahead of `update()` so a cycle attempt gets its own field-level message rather than a generic one
        if ($parentId > PlaylistFolder::ROOT && $this->playlistFolderRepository->wouldCycle($folder->getId(), $parentId)) {
            AmpError::add('parent', T_('A folder cannot contain itself'));
        }

        if (!AmpError::occurred() && !$this->playlistFolderRepository->update($folder->getId(), $name, $parentId)) {
            AmpError::add('name', T_('That name already exists'));
        }

        if (AmpError::occurred()) {
            echo new PlaylistFolderFormView(
                $this->configContainer->getWebPath('/client'),
                $folder->getId(),
                $name,
                $parentId,
                $this->treeFormatter->flatten($user, $folder->getId())
            )->render();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $this->ui->showConfirmation(
            T_('Folder updated'),
            $name,
            sprintf(
                '%s/browse.php?action=playlist_folder%s',
                $this->configContainer->getWebPath('/client'),
                ($parentId > PlaylistFolder::ROOT) ? '&folder=' . $parentId : ''
            )
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
