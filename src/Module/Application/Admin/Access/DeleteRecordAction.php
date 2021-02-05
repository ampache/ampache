<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AccessRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteRecordAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete_record';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private AccessRepositoryInterface $accessRepository;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        AccessRepositoryInterface $accessRepository
    ) {
        $this->ui               = $ui;
        $this->configContainer  = $configContainer;
        $this->accessRepository = $accessRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false ||
            !Core::form_verify('delete_access')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $this->accessRepository->delete(
            (int) $request->getQueryParams()['access_id'] ?? 0
        );

        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('Your Access List entry has been removed'),
            sprintf(
                '%s/admin/access.php',
                $this->configContainer->getWebPath()
            )
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
