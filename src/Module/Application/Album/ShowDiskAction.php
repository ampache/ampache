<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowDiskAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_disk';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private PrivilegeCheckerInterface $privilegeChecker;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        LoggerInterface $logger,
        PrivilegeCheckerInterface $privilegeChecker,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory     = $modelFactory;
        $this->ui               = $ui;
        $this->logger           = $logger;
        $this->privilegeChecker = $privilegeChecker;
        $this->configContainer  = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $user        = $gatekeeper->getUser() ?? $this->modelFactory->createUser(-1);
        $catalogs    = (isset($user->catalogs['music'])) ? $user->catalogs['music'] : User::get_user_catalogs($user->id);
        $albumDiskId = (int) ($request->getQueryParams()['album_disk'] ?? 0);
        $albumDisk   = $this->modelFactory->createAlbumDisk($albumDiskId);

        if ($albumDisk->isNew() || !in_array($albumDisk->catalog, $catalogs)) {
            $this->logger->warning(
                'Requested an album_disk that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $this->ui->show(
                'show_album_disk.inc.php',
                [
                    'albumDisk' => $albumDisk,
                    'isAlbumEditable' => $this->isEditable(
                        $gatekeeper,
                        $albumDisk
                    ),
                    'user' => $user
                ]
            );
        }

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    private function isEditable(
        GuiGatekeeperInterface $gatekeeper,
        AlbumDisk $albumDisk
    ): bool {
        if (
            $this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
        ) {
            return true;
        }

        if (!$albumDisk->album_artist) {
            return false;
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT) === false) {
            return false;
        }

        return $albumDisk->get_user_owner() === $gatekeeper->getUserId();
    }
}
