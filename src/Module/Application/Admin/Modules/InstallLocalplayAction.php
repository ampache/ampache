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

namespace Ampache\Module\Application\Admin\Modules;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\PluginManagerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class InstallLocalplayAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'install_localplay';

    public function __construct(
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
        private PluginManagerInterface $pluginManager,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false
            || !$this->requestParser->verifyForm('install_localplay')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $user = Core::get_global('user');
        if ($user === null) {
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        // The manager loads the controller, installs it and applies the localplay enable/level/controller preferences
        // to every user (-1), matching the CLI installer's default rather than only the clicking admin
        $type = (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$this->pluginManager->installLocalplay($type, -1)) {
            AmpError::add('general', T_('Failed to enable the Localplay module'));
            echo AmpError::display('general');

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        /* Show Confirmation */
        $url   = sprintf('%s/modules.php?action=show_localplay', $this->configContainer->getWebPath('/admin'));
        $title = T_('No Problem');
        $body  = T_('Localplay has been enabled');
        $this->ui->showConfirmation($title, $body, $url);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
