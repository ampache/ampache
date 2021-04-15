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
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Retrieve the content of an external url
 */
final class ExternalResourceLoader implements ExternalResourceLoaderInterface
{
    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private UtilityFactoryInterface $utilityFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        UtilityFactoryInterface $utilityFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
        $this->utilityFactory  = $utilityFactory;
    }

    public function retrieve(
        string $url,
        ?array $options = null
    ): ?ResponseInterface {
        $client  = $this->utilityFactory->createHttpClient();
        $options = $options
            ?: $this->getRequestsOptions();

        $options['headers'] = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0',
        ];

        try {
            return $client->request(
                'GET',
                $url,
                $options
            );
        } catch (GuzzleException $e) {
            $this->logger->error(
                sprintf('Web request failed: %s', $e->getMessage()),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return null;
        }
    }

    private function getRequestsOptions(): array
    {
        $options = [];

        $proxyConfig = $this->configContainer->getProxyOptions();

        if ($proxyConfig[ConfigurationKeyEnum::PROXY_HOST] && $proxyConfig[ConfigurationKeyEnum::PROXY_PORT]) {
            $credentials = '';

            if ($proxyConfig[ConfigurationKeyEnum::PROXY_USER]) {
                $credentials = sprintf(
                    '%s:%s@',
                    $proxyConfig[ConfigurationKeyEnum::PROXY_USER],
                    $proxyConfig[ConfigurationKeyEnum::PROXY_PASS]
                );
            }

            $options['proxy'] = sprintf(
                'http://%s%s:%d',
                $credentials,
                $proxyConfig[ConfigurationKeyEnum::PROXY_HOST],
                $proxyConfig[ConfigurationKeyEnum::PROXY_PORT]
            );
        }

        return $options;
    }
}
