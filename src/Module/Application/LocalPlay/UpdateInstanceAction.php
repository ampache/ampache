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

namespace Ampache\Module\Application\LocalPlay;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class UpdateInstanceAction extends AbstractLocalPlayAction
{
    public const REQUEST_KEY = 'update_instance';

    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory
    ) {
        parent::__construct($configContainer);
        $this->configContainer = $configContainer;
        $this->responseFactory = $responseFactory;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper
    ): ?ResponseInterface {
        // This requires 75 or better!
        if ($gatekeeper->mayAccess(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::MANAGER) === false) {
            throw new AccessDeniedException();
        }

        // Setup the object
        $localplay = new LocalPlay($this->configContainer->get(ConfigurationKeyEnum::LOCALPLAY_CONTROLLER));
        $localplay->update_instance($_REQUEST['instance'], $_POST);

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf('%s/localplay.php?action=show_instances', $this->configContainer->getWebPath())
            );
    }
}
