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

namespace Ampache\Module\Application\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            $this->ui->showHeader();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::LABEL)) {
            throw new AccessDeniedException('Access Denied: label features are not enabled.');
        }

        $labelId = (int) ($request->getQueryParams()['label_id'] ?? 0);

        $this->ui->showHeader();

        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) === true
        ) {
            $this->ui->showConfirmation(
                T_('Are You Sure?'),
                T_('This Label will be deleted'),
                sprintf(
                    '%s/labels.php?action=confirm_delete&label_id=%s',
                    $this->configContainer->getWebPath('/client'),
                    $labelId
                ),
                1,
                'delete_label'
            );
        } else {
            throw new AccessDeniedException();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
