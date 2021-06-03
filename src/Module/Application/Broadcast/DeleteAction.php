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

declare(strict_types=0);

namespace Ampache\Module\Application\Broadcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\BroadcastRepositoryInteface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private BroadcastRepositoryInteface $broadcastRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        BroadcastRepositoryInteface $broadcastRepository
    ) {
        $this->configContainer     = $configContainer;
        $this->ui                  = $ui;
        $this->modelFactory        = $modelFactory;
        $this->broadcastRepository = $broadcastRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::BROADCAST) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true
        ) {
            throw new AccessDeniedException();
        }

        $broadcast = $this->modelFactory->createBroadcast(
            (int) ($request->getQueryParams()['id'] ?? 0)
        );

        $this->broadcastRepository->delete($broadcast);

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('Broadcast has been deleted'),
            sprintf(
                '%s/browse.php?action=broadcast',
                $this->configContainer->getWebPath()
            )
        );
        $this->ui->showFooter();

        return null;
    }
}
