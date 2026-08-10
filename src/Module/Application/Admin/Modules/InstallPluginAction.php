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
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginManagerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class InstallPluginAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'install_plugin';

    public function __construct(
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private LoggerInterface $logger,
        private PluginManagerInterface $pluginManager,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false
            || !$this->requestParser->verifyForm('install_plugin')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $plugin_name = $this->requestParser->getFromRequest('plugin');

        /* Verify that this plugin exists */
        $plugins = Plugin::get_plugins();
        if (!array_key_exists($plugin_name, $plugins)) {
            $this->logger->error(
                sprintf('Error: Invalid Plugin: %s selected', $plugin_name),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        // Existence is confirmed above, so a false result here is an install failure; the manager also runs the
        // post-install preference rebuild that must never be skipped
        if (!$this->pluginManager->installPlugin($plugin_name)) {
            $this->logger->error(
                sprintf('Error: Plugin Install Failed, %s', $plugin_name),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            $url   = sprintf('%s/modules.php?action=show_plugins', $this->configContainer->getWebPath('/admin'));
            $title = T_('There Was a Problem');
            $body  = T_('Unable to install this Plugin');
            $this->ui->showConfirmation($title, $body, $url);

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        /* Show Confirmation */
        $url   = sprintf('%s/modules.php?action=show_plugins', $this->configContainer->getWebPath('/admin'));
        $title = T_('No Problem');
        $body  = T_('The Plugin has been enabled');
        $this->ui->showConfirmation($title, $body, $url);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
