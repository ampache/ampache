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

namespace Ampache\Module\WebDav;

use Ampache\Config\ConfigContainerInterface;

final class WebDavApplication
{
    private ConfigContainerInterface $configContainer;

    private WebDavFactoryInterface $webDavFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        WebDavFactoryInterface $webDavFactory
    ) {
        $this->configContainer = $configContainer;
        $this->webDavFactory   = $webDavFactory;
    }

    public function run(): void
    {
        if ($this->configContainer->isWebDavBackendEnabled() === false) {
            echo T_('Disabled');

            return;
        }

        $server = $this->webDavFactory->createServer(
            $this->webDavFactory->createWebDavCatalog()
        );

        $raw_web_path = $this->configContainer->getRawWebPath();
        if ($raw_web_path === '/') {
            $raw_web_path = '';
        }

        $server->setBaseUri(
            sprintf('%s/webdav/index.php', $raw_web_path)
        );

        if ($this->configContainer->isAuthenticationEnabled()) {
            $server->addPlugin(
                $this->webDavFactory->createPlugin(
                    $this->webDavFactory->createWebDavAuth()
                )
            );
        }

        $server->exec();
    }
}
