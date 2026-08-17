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

namespace Ampache\Module\Application\Admin\Filter;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Form\ManageFiltersView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CatalogFilterRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders the catalog-filters list
 */
final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private CatalogFilterRepositoryInterface $catalogFilterRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();
        $this->ui->showBoxTop('Show Catalog Filters', 'box box_manage_filter');

        $filters = [];
        foreach ($this->catalogFilterRepository->findGroups() as $filter) {
            $filterId  = (int) $filter['id'];
            $filters[] = [
                'id' => $filterId,
                'name' => (string) $filter['name'],
                'userCount' => $this->userRepository->countByCatalogFilterGroup($filterId),
                'catalogCount' => $this->catalogFilterRepository->countCatalogs($filterId),
            ];
        }

        echo new ManageFiltersView(
            $this->configContainer->getWebPath('/admin'),
            $filters,
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
        )->render();
        $this->ui->showBoxBottom();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
