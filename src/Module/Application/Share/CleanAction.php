<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Cleans up expired share items
 */
final class CleanAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'clean';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ShareRepositoryInterface $shareRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ShareRepositoryInterface $shareRepository
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->shareRepository = $shareRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            !$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE) ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
        ) {
            throw new AccessDeniedException('Access Denied: sharing not available.');
        }

        $this->shareRepository->collectGarbage();

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('Expired shares have been cleaned'),
            sprintf(
                '%s/stats.php?action=share',
                $this->configContainer->getWebPath()
            )
        );
        $this->ui->showFooter();

        return null;
    }
}
