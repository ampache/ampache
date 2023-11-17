<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\DatabaseCharsetUpdaterInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ResetDbCharsetAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'reset_db_charset';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private DatabaseCharsetUpdaterInterface $databaseCharsetUpdater;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        DatabaseCharsetUpdaterInterface $databaseCharsetUpdater
    ) {
        $this->configContainer        = $configContainer;
        $this->ui                     = $ui;
        $this->databaseCharsetUpdater = $databaseCharsetUpdater;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false ||
            $this->configContainer->isDemoMode() === true
        ) {
            throw new AccessDeniedException();
        }

        $this->databaseCharsetUpdater->update();

        $this->ui->showHeader();

        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('Your database and associated tables have been updated to match your currently configured charset'),
            'admin/system.php?action=show_debug'
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
