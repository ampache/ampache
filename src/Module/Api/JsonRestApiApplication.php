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

namespace Ampache\Module\Api;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Output\ApiOutputFactoryInterface;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\ResponseEmitter;

final class JsonRestApiApplication implements ApiApplicationInterface
{
    private ApiHandlerInterface $apiHandler;
    private ApiOutputFactoryInterface $apiOutputFactory;
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
        ServerRequestCreatorInterface $serverRequestCreator,
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
        header(sprintf('Content-type: application/json; charset=%s', $this->configContainer->get('site_charset')));

        $request = $this->serverRequestCreator->fromGlobals();
        $method  = strtoupper($request->getMethod());
        $input   = $request->getQueryParams();

        // normalize input types (REST paths)
        $type = (!empty($input['type']))
            ? $this->apiHandler->normalizeType((string) $input['type'])
            : null;

        // catalog task shortcuts (e.g. `POST catalogs/{catalog_id}/clean`) are aliases of
        // catalog_action, so derive the task from the path before the action is normalized
        $task = ($type === 'catalog' && isset($input['filter']))
            ? match ((string) ($input['action'] ?? '')) {
                'add' => 'add_to_catalog',
                'clean' => 'clean_catalog',
                'update' => 'update_catalog',
                'verify' => 'verify_catalog',
                default => null,
            }
        : null;

        // normalize input actions (REST paths)
        $action = $this->apiHandler->normalizeAction((string) ($input['action'] ?? ''), $type, isset($input['filter']));
        // an empty action is handled by the api handler; don't turn it into a method suffix
        $action = ($action === '')
            ? $action
            : match ($method) {
                'DELETE' => $action . '_delete',
                'PATCH' => $action . '_edit',
                'PUT' => $action . '_create',
                default => $action,
            };

        // filter out bad requests
        if ($action === 'register' && $method !== 'POST') {
            $action = 'bad_request';
        }

        $parameters = [
            'action' => $action,
            'api_format' => 'json'
        ];

        if ($type !== null && $type !== '') {
            $parameters['type'] = $type;
        }

        if ($task !== null) {
            $parameters['task'] = $task;
        }

        $post = (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE']))
            ? (array) $request->getParsedBody()
            : [];

        $request = $request->withQueryParams(
            array_merge(
                $request->getQueryParams(),
                $post,
                $parameters
            )
        );

        $response = $this->apiHandler->handle(
            $request,
            $response,
            $this->apiOutputFactory->createJsonOutput()
        );

        // @todo remove condition after all api methods have been modernized
        if ($response !== null) {
            $this->sapiEmitter->emit($response);
        }
    }
}
