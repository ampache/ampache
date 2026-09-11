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

namespace Ampache\Module\Application\Admin\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowDeleteAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_delete';

    public function __construct(
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private ShoutRepositoryInterface $shoutRepository,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $shoutId = (int) ($request->getQueryParams()['shout_id'] ?? 0);

        $shout = $this->shoutRepository->findById($shoutId);
        if ($shout === null) {
            throw new ObjectNotFoundException($shoutId);
        }

        $this->ui->showConfirmation(
            T_('Are You Sure?'),
            sprintf(T_('This will permanently delete the shoutbox post "%s"'), scrub_out($shout->getText())),
            sprintf(
                '%s/shout.php?action=delete&shout_id=%d',
                $this->configContainer->getWebPath('/admin'),
                $shoutId
            ),
            1,
            'delete_shout'
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
