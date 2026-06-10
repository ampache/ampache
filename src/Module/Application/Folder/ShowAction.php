<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Folder;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Folder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private PrivilegeCheckerInterface $privilegeChecker,
        private FolderRepositoryInterface $folderRepository,
    ) {
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::LABEL)) {
            throw new AccessDeniedException('Access Denied: folder features are not enabled.');
        }

        $this->ui->showHeader();

        $input = $request->getQueryParams();

        // lookup by ID
        $folder_id = (isset($input['folder'])) ? (int)$input['folder'] : null;
        $folder    = (is_int($folder_id))
            ? $this->folderRepository->findById($folder_id)
            : null;
        // lookup by name if ID didn't work
        $folder_name = (isset($input['name'])) ? urldecode((string)$input['name']) : null;
        if (!$folder && $folder_name !== null) {
            $folder_id = $this->folderRepository->lookup($folder_name);
            $folder    = ($folder_id > 0)
                ? $this->folderRepository->findById($folder_id)
                : null;
        }

        if ($folder_id !== null && $folder === null) {
            $this->logger->warning(
                'Requested a folder that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
            $this->ui->showFooter();

            return null;
        } elseif ($folder instanceof Folder) {
            $this->ui->show(
                'show_folder.inc.php',
                [
                    'folder' => $folder,
                    'object_ids' => $folder->get_children(),
                ]
            );

            $this->ui->showFooter();

            return null;
        }

        // if you didn't set a folder_id or name, show the add folder form
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) {
            $this->ui->show(
                'show_add_folder.inc.php'
            );
        } else {
            throw new AccessDeniedException();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
