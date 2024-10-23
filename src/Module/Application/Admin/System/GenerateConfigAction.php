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

namespace Ampache\Module\Application\Admin\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\InstallationHelperInterface;
use Ampache\Module\Util\Horde_Browser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class GenerateConfigAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'generate_config';

    private ConfigContainerInterface $configContainer;

    private Horde_Browser $browser;

    private InstallationHelperInterface $installationHelper;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        Horde_Browser $browser,
        InstallationHelperInterface $installationHelper,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->configContainer    = $configContainer;
        $this->browser            = $browser;
        $this->installationHelper = $installationHelper;
        $this->responseFactory    = $responseFactory;
        $this->streamFactory      = $streamFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true
        ) {
            throw new AccessDeniedException();
        }

        $generatedConfig = $this->installationHelper->generate_config(
            parse_ini_file($this->configContainer->getConfigFilePath()) ?: []
        );

        $headers = $this->browser->getDownloadHeaders(
            'ampache.cfg.php',
            'text/plain',
            false,
            (string)strlen($generatedConfig)
        );

        $response = $this->responseFactory->createResponse();

        foreach ($headers as $headerName => $value) {
            $response = $response->withHeader($headerName, $value);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $generatedConfig
            )
        );
    }
}
