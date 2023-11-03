<?php

/*
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

declare(strict_types=0);

namespace Ampache\Module\Application\Admin\License;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private LicenseRepositoryInterface $licenseRepository;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        LicenseRepositoryInterface $licenseRepository
    ) {
        $this->ui                = $ui;
        $this->configContainer   = $configContainer;
        $this->modelFactory      = $modelFactory;
        $this->licenseRepository = $licenseRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException();
        }

        $license = $this->modelFactory->createLicense((int)($request->getQueryParams()['license_id'] ?? 0));

        $this->licenseRepository->delete($license->id);

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('The License has been deleted'),
            sprintf('%s/admin/license.php', $this->configContainer->getWebPath())
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
