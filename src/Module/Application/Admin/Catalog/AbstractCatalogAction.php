<?php

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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Catalog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractCatalogAction implements ApplicationActionInterface
{
    public function __construct(private readonly UiInterface $ui)
    {
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $catalogIds = $this->getCatalogIds();

        if ($catalogIds !== null) {
            $this->handle($request, $catalogIds);
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    abstract protected function handle(
        ServerRequestInterface $request,
        array $catalogIds,
    ): ?ResponseInterface;

    protected function getCatalogIds(): ?array
    {
        $catalogs = (isset($_REQUEST['catalogs']))
            ? filter_var_array($_REQUEST['catalogs'], FILTER_SANITIZE_NUMBER_INT)
            : [];

        // If only one catalog, check it is ready.
        if (count($catalogs) == 1) {
            // If not ready, display the data to make it ready / stop the action.
            $catalog = Catalog::create_from_id((int)$catalogs[0]);
            if ($catalog !== null && !$catalog->isReady()) {
                if (!isset($_REQUEST['perform_ready'])) {
                    $catalog->show_ready_process();

                    return null;
                }

                $catalog->perform_ready();
            }
        }

        return $catalogs;
    }
}
