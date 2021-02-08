<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\EnvironmentInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class GoodbyeMethod implements MethodInterface
{
    public const ACTION = 'goodbye';

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    private EnvironmentInterface $environment;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger,
        EnvironmentInterface $environment
    ) {
        $this->streamFactory = $streamFactory;
        $this->logger        = $logger;
        $this->environment   = $environment;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Destroy session for auth key.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * auth = (string))
     *
     * @return ResponseInterface
     *
     * @throws Exception\RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $auth = $input['auth'] ?? null;

        if ($auth === null) {
            throw new Exception\RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'auth')
            );
        }

        // Check and see if we should destroy the api session (done if valid session is passed)
        if ($gatekeeper->sessionExists()) {
            $gatekeeper->endSession();

            $this->logger->debug(
                sprintf('Goodbye Received from %s', $this->environment->getClientIp()),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success($auth)
                )
            );
        }

        throw new Exception\RequestParamMissingException(
            sprintf(T_('Bad Request: %s'), 'auth')
        );
    }
}
