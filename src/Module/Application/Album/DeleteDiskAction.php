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

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class DeleteDiskAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'delete_disk';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private DeletionUrlResolverInterface $deletionUrlResolver,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            $this->ui->showHeader();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $queryParams = $request->getQueryParams();
        $albumDiskId = (int) ($queryParams['album_disk'] ?? 0);
        $burlParam   = (string) ($queryParams['burl'] ?? '');

        $this->ui->showHeader();
        if ($albumDiskId < 1) {
            $this->logger->warning(
                'Requested an album disk that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $webPath = $this->configContainer->getWebPath('/client');

            $this->ui->showConfirmationWithReturn(
                T_('Are You Sure?'),
                T_('The Disk and all files will be deleted'),
                sprintf(
                    '%s/albums.php?action=%s&album_disk=%d&burl=%s',
                    $webPath,
                    ConfirmDeleteDiskAction::REQUEST_KEY,
                    $albumDiskId,
                    rawurlencode($burlParam)
                ),
                $this->deletionUrlResolver->resolveBurl($burlParam) ?: sprintf(
                    '%s/albums.php?action=show_disk&album_disk=%d',
                    $webPath,
                    $albumDiskId
                ),
                'delete_album_disk'
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
