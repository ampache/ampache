<?php

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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteCatalogAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete_catalog';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private RequestParserInterface $requestParser;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        RequestParserInterface $requestParser
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
        $this->requestParser   = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            check_http_referer() === false ||
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true ||
            !$this->requestParser->verifyForm('delete_catalog')
        ) {
            throw new AccessDeniedException();
        }
        $deleted  = false;
        $catalogs = (isset($_REQUEST['catalogs']))
            ? filter_var_array($_REQUEST['catalogs'], FILTER_SANITIZE_NUMBER_INT)
            : [];
        if (is_array($catalogs) && !empty($catalogs)) {
            $deleted = true;
            // Delete the sucker, we don't need to check perms as that's done above
            foreach ($catalogs as $catalog_id) {
                $deleted = Catalog::delete((int)$catalog_id);
                if (!$deleted) {
                    break;
                }
            }
        }

        $this->ui->showHeader();

        $next_url = sprintf('%s/catalog.php', $this->configContainer->getWebPath('/admin'));
        if ($deleted) {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Catalog has been deleted'),
                $next_url
            );
        } else {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                /* HINT: Artist, Album, Song, Catalog, Video, Catalog Filter */
                sprintf(T_('Couldn\'t delete this %s'), T_('Catalog')),
                $next_url
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
