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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\EnvironmentInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class PingMethod implements MethodInterface
{
    public const ACTION = 'ping';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private EnvironmentInterface $environment;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        EnvironmentInterface $environment
    ) {
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
        $this->environment     = $environment;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This can be called without being authenticated, it is useful for determining if what the status
     * of the server is, and what version it is running/compatible with
     *
     * @param GatekeeperInterface
     * @param ResponseInterface
     * @param ApiOutputInterface
     * @param array $input
     * auth = (string) //optional
     *
     * @return ResponseInterface
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        // set the version to the old string for old api clients
        $version      = (isset($input['version'])) ? $input['version'] : Api::$version;
        Api::$version = ($version[0] === '4' || $version[0] === '3') ? '500000' : Api::$version;

        $data = [
            'server' => $this->configContainer->get(ConfigurationKeyEnum::VERSION),
            'version' => Api::$version,
            'compatible' => '350001'
        ];

        // Check and see if we should extend the api sessions (done if valid session is passed)
        if ($gatekeeper->sessionExists()) {
            $gatekeeper->extendSession();

            $data = array_merge(
                ['session_expire' => date('c', time() + $this->configContainer->getSessionLength() - 60)],
                $data,
                Api::server_details($input['auth'])
            );
        }

        $this->logger->debug(
            sprintf('Ping Received from %s', $this->environment->getClientIp()),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->dict($data)
            )
        );
    }
}
