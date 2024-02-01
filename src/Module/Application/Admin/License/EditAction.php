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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'edit';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private LicenseRepositoryInterface $licenseRepository;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        LicenseRepositoryInterface $licenseRepository
    ) {
        $this->ui                = $ui;
        $this->configContainer   = $configContainer;
        $this->licenseRepository = $licenseRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException();
        }

        $data      = $request->getParsedBody();
        $licenseId = (int) ($data['license_id'] ?? 0);

        if ($licenseId > 0) {
            $license = $this->licenseRepository->findById($licenseId);

            if ($license !== null) {
                $this->licenseRepository->update(
                    $license,
                    (array_key_exists('name', $data)) ? htmlspecialchars($data['name']) : '',
                    (array_key_exists('description', $data)) ? htmlspecialchars($data['description']) : '',
                    (array_key_exists('external_link', $data)) ? (string)filter_var($data['external_link'], FILTER_SANITIZE_URL) : ''
                );
            }
            $text = T_('The License has been updated');
        } else {
            $this->licenseRepository->create(
                (array_key_exists('name', $data)) ? htmlspecialchars($data['name']) : '',
                (array_key_exists('description', $data)) ? htmlspecialchars($data['description']) : '',
                (array_key_exists('external_link', $data)) ? (string)filter_var($data['external_link'], FILTER_SANITIZE_URL) : ''
            );
            $text = T_('A new License has been created');
        }

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('No Problem'),
            $text,
            sprintf('%s/admin/license.php', $this->configContainer->getWebPath())
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
