<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Admin\Modules;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InstallCatalogTypeAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'install_catalog_type';

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
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false ||
            !$this->requestParser->verifyForm('install_catalog_type')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $type    = scrub_in((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        $catalog = Catalog::create_catalog_type($type);
        if ($catalog === null) {
            AmpError::add('general', T_('Failed to enable the Catalog module'));
            echo AmpError::display('general');

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $catalog->install();

        /* Show Confirmation */
        $url   = sprintf('%s/modules.php?action=show_catalog_types', $this->configContainer->getWebPath('/admin'));
        $title = T_('No Problem');
        $body  = T_('The Module has been enabled');
        $this->ui->showConfirmation($title, $body, $url);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
