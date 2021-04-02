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

declare(strict_types=1);

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Requests;
use Requests_Exception;

/**
 * Retrieve the content of an external url
 */
final class ExternalResourceLoader implements ExternalResourceLoaderInterface
{
    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function retrieve(
        string $url
    ): ?ResponseInterface {
        try {
            // Need this to not be considered as a bot (are we? ^^)
            $result = Requests::get(
                $url,
                [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0',
                ],
                $this->getRequestsOptions()
            );
        } catch (Requests_Exception $e) {
            $this->logger->error(
                sprintf('Error getting google images: %s', $e->getMessage()),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return null;
        }

        $response = $this->responseFactory->createResponse((int) $result->status_code);
        $response->getBody()->write($result->body);

        return $response->withHeader('Content-Type', $result->headers['Content-Type'] ?? '');
    }

    private function getRequestsOptions(): array
    {
        $options = [];

        $proxyConfig = $this->configContainer->getProxyOptions();

        if ($proxyConfig[ConfigurationKeyEnum::PROXY_HOST] && $proxyConfig[ConfigurationKeyEnum::PROXY_PORT]) {
            $proxy   = [];
            $proxy[] = sprintf(
                '%s:%d',
                $proxyConfig[ConfigurationKeyEnum::PROXY_HOST],
                $proxyConfig[ConfigurationKeyEnum::PROXY_PORT]
            );
            if ($proxyConfig[ConfigurationKeyEnum::PROXY_USER]) {
                $proxy[] = $proxyConfig[ConfigurationKeyEnum::PROXY_USER];
                $proxy[] = $proxyConfig[ConfigurationKeyEnum::PROXY_PASS];
            }

            $options['proxy'] = $proxy;
        }

        return $options;
    }
}
