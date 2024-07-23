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

namespace Ampache\Module\Api;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Output\ApiOutputFactoryInterface;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\ResponseEmitter;

final class XmlApiApplication implements ApiApplicationInterface
{
    private ApiOutputFactoryInterface $apiOutputFactory;

    private ApiHandlerInterface $apiHandler;

    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    private ResponseEmitter $sapiEmitter;

    private ServerRequestCreatorInterface $serverRequestCreator;

    public function __construct(
        ApiOutputFactoryInterface $apiOutputFactory,
        ApiHandlerInterface $apiHandler,
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory,
        ResponseEmitter $sapiEmitter,
        ServerRequestCreatorInterface $serverRequestCreator
    ) {
        $this->apiOutputFactory     = $apiOutputFactory;
        $this->apiHandler           = $apiHandler;
        $this->configContainer      = $configContainer;
        $this->responseFactory      = $responseFactory;
        $this->sapiEmitter          = $sapiEmitter;
        $this->serverRequestCreator = $serverRequestCreator;
    }

    public function run(): void
    {
        $response = $this->responseFactory->createResponse();

        // @todo add headers to response after all api methods have been modernized
        /* Set the correct headers */
        header(sprintf('Content-type: text/xml; charset=%s', $this->configContainer->get('site_charset')));
        header('Content-Disposition: attachment; filename=information.xml');

        $request = $this->serverRequestCreator->fromGlobals();
        $request = $request->withQueryParams(
            array_merge(
                ['api_format' => 'xml'],
                $request->getQueryParams()
            )
        );

        $response = $this->apiHandler->handle(
            $request,
            $response,
            $this->apiOutputFactory->createXmlOutput()
        );

        // @todo remove condition after all api methods have been modernized
        if ($response !== null) {
            $this->sapiEmitter->emit($response);
        }
    }
}
