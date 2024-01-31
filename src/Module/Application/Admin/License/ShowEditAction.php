<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Admin\License;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowEditAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_edit';

    private UiInterface $ui;

    private LicenseRepositoryInterface $licenseRepository;

    public function __construct(
        UiInterface $ui,
        LicenseRepositoryInterface $licenseRepository
    ) {
        $this->ui                = $ui;
        $this->licenseRepository = $licenseRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException();
        }

        $licenseId = (int) ($request->getQueryParams()['license_id'] ?? 0);

        $license = $this->licenseRepository->findById($licenseId);

        if ($license === null) {
            throw new ObjectNotFoundException($licenseId);
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_edit_license.inc.php',
            ['license' => $license]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
