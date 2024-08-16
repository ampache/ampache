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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class WriteConfigAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'write_config';

    private ConfigContainerInterface $configContainer;

    private InstallationHelperInterface $installationHelper;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        InstallationHelperInterface $installationHelper,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->configContainer    = $configContainer;
        $this->installationHelper = $installationHelper;
        $this->responseFactory    = $responseFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true
        ) {
            throw new AccessDeniedException();
        }

        if ($this->installationHelper->write_config(__DIR__ . '/../../../../../config/ampache.cfg.php')) {
            return $this->responseFactory->createResponse(StatusCode::FOUND)
                ->withHeader(
                    'Location',
                    sprintf('%s/index.php', $this->configContainer->getWebPath('/client'))
                );
        }

        return $this->responseFactory->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf('%s/error.php?permission=%s', $this->configContainer->getWebPath('/client'), rawurlencode('config/ampache.cfg.php'))
            );
    }
}
