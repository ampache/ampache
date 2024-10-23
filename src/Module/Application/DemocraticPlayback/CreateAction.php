<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\DemocraticPlayback;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Democratic;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    private RequestParserInterface $requestParser;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory,
        RequestParserInterface $requestParser
    ) {
        $this->configContainer = $configContainer;
        $this->responseFactory = $responseFactory;
        $this->requestParser   = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Make sure they have access to this */
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_DEMOCRATIC_PLAYBACK) === false ||
            !$this->requestParser->verifyForm('create_democratic') ||
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false
        ) {
            throw new AccessDeniedException();
        }

        $democratic = Democratic::get_current_playlist();

        // If we don't have anything currently create something
        if ($democratic->isNew()) {
            // Create the playlist
            Democratic::create($_POST);
        } else {
            $democratic->update($_POST);
        }

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/democratic.php?action=show',
                    $this->configContainer->getWebPath()
                )
            );
    }
}
