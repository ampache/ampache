<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

namespace Ampache\Module\Util\WebFetcher;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use Curl\Curl;
use Psr\Log\LoggerInterface;

/**
 * Provides functionality for downloading web-content
 */
final class WebFetcher implements WebFetcherInterface
{
    /** @var int Curl operation timeout in seconds */
    private const TIMEOUT = 300;

    private ConfigContainerInterface $config;

    private UtilityFactoryInterface $utilityFactory;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $config,
        UtilityFactoryInterface $utilityFactory,
        LoggerInterface $logger
    ) {
        $this->config         = $config;
        $this->utilityFactory = $utilityFactory;
        $this->logger         = $logger;
    }

    /**
     * Fetches and returns the uris content
     *
     * @throws FetchFailedException
     */
    public function fetch(string $uri): string
    {
        $curl = $this->setupCurl();

        $this->logger->debug(
            sprintf('Fetching url: %s', $uri),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        $curl->get($uri);
        $curl->close();

        if ($curl->error) {
            throw new Exception\FetchFailedException(
                sprintf('Error fetching url: %s', $uri)
            );
        }

        return (string) $curl->rawResponse;
    }

    /**
     * Fetches the uris content and saves it directly to a file
     *
     * @throws FetchFailedException
     */
    public function fetchToFile(
        string $uri,
        string $destinationFilePath
    ): void {
        $curl = $this->setupCurl();
        $curl->setReferer($uri);

        $result = $curl->download($uri, $destinationFilePath);

        $curl->close();
        if ($result) {
            $this->logger->debug(
                sprintf('Download to file completed: %s', $destinationFilePath),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        } else {
            throw new Exception\FetchFailedException(
                sprintf('Error downloading to file: %s. Reason: %s', $destinationFilePath, $curl->errorMessage)
            );
        }
    }

    /**
     * Sets up the curl session with configured defaults
     */
    private function setupCurl(): Curl
    {
        $proxyHost = $this->config->get(ConfigurationKeyEnum::PROXY_HOST);
        $proxyPort = $this->config->get(ConfigurationKeyEnum::PROXY_PORT);
        $proxyUser = $this->config->get(ConfigurationKeyEnum::PROXY_USER);
        $proxyPass = $this->config->get(ConfigurationKeyEnum::PROXY_PASS);

        $curl = $this->utilityFactory->createCurl();
        $curl->setFollowLocation();
        $curl->setTimeout(self::TIMEOUT);
        $curl->setUserAgent(sprintf('Ampache/%s', $this->config->getVersion()));

        if ($proxyHost && $proxyPort) {
            if ($proxyUser === '') {
                $proxyUser = null;
            }
            if ($proxyPass === '') {
                $proxyPass = null;
            }
            $curl->setProxy($proxyHost, $proxyPort, $proxyUser, $proxyPass);
        }

        return $curl;
    }
}
