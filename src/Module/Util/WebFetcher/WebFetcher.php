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

namespace Ampache\Module\Util\WebFetcher;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UrlValidatorInterface;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use ArrayAccess;
use Curl\Curl;
use Psr\Log\LoggerInterface;

/**
 * Provides functionality for downloading web-content
 */
final readonly class WebFetcher implements WebFetcherInterface
{
    /** @var int How many redirects a fetch will follow, each one checked before it is followed */
    private const int MAX_REDIRECTS = 10;
    /** @var int Curl operation timeout in seconds */
    private const int TIMEOUT = 300;

    public function __construct(
        private ConfigContainerInterface $config,
        private UtilityFactoryInterface $utilityFactory,
        private LoggerInterface $logger,
        private UrlValidatorInterface $urlValidator,
    ) {}

    /**
     * Fetches and returns the uris content
     *
     * @throws FetchFailedException
     */
    public function fetch(string $uri): string
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $this->assertFetchable($uri);

            $curl = $this->setupCurl();

            $this->logger->debug(
                sprintf('Fetching url: %s', $uri),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            $curl->get($uri);
            $response = (string) $curl->rawResponse;
            $error    = $curl->error;
            $location = $this->getRedirect($curl, $uri);
            $curl->close();

            if ($location !== null) {
                $uri = $location;
                continue;
            }

            if ($error) {
                throw new FetchFailedException(
                    sprintf('Error fetching url: %s', $uri)
                );
            }

            return $response;
        }

        throw new FetchFailedException(
            sprintf('Too many redirects fetching url: %s', $uri)
        );
    }

    /**
     * Fetches the uris content and saves it directly to a file
     *
     * @throws FetchFailedException
     */
    public function fetchToFile(
        string $uri,
        string $destinationFilePath,
    ): void {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $this->assertFetchable($uri);

            $curl = $this->setupCurl();
            $curl->setReferer($uri);

            $result       = $curl->download($uri, $destinationFilePath);
            $errorMessage = (string) $curl->errorMessage;
            $location     = $this->getRedirect($curl, $uri);
            $curl->close();

            if ($location !== null) {
                // the redirect body landed in the destination, so it goes before the next hop is tried
                @unlink($destinationFilePath);
                $uri = $location;
                continue;
            }

            if (!$result) {
                throw new FetchFailedException(
                    sprintf('Error downloading to file: %s. Reason: %s', $destinationFilePath, $errorMessage)
                );
            }

            $this->logger->debug(
                sprintf('Download to file completed: %s', $destinationFilePath),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return;
        }

        @unlink($destinationFilePath);

        throw new FetchFailedException(
            sprintf('Too many redirects downloading url: %s', $uri)
        );
    }

    /**
     * Refuses a url the server must not request on someone else's behalf
     *
     * @throws FetchFailedException
     */
    private function assertFetchable(string $uri): void
    {
        if (!$this->urlValidator->isPublicHttpUrl($uri)) {
            $this->logger->warning(
                sprintf('Refusing to fetch url: %s', $uri),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            throw new FetchFailedException(
                sprintf('Refusing to fetch url: %s', $uri)
            );
        }
    }

    /**
     * The absolute url a redirect response points at, or null when the response is not a redirect
     *
     * Redirects are followed by hand because curl would follow them without asking whether the target may be reached.
     */
    private function getRedirect(Curl $curl, string $uri): ?string
    {
        $status = (int) $curl->httpStatusCode;
        if ($status < 300 || $status > 399) {
            return null;
        }

        $headers  = $curl->getResponseHeaders();
        $location = (is_array($headers) || $headers instanceof ArrayAccess)
            ? (string) ($headers['Location'] ?? '')
            : '';

        if ($location === '') {
            return null;
        }

        // a relative Location is resolved against the url that answered with it
        if (parse_url($location, PHP_URL_SCHEME) === null) {
            $base     = parse_url($uri);
            $location = sprintf(
                '%s://%s%s%s',
                $base['scheme'] ?? 'http',
                $base['host'] ?? '',
                isset($base['port']) ? ':' . $base['port'] : '',
                str_starts_with($location, '/') ? $location : '/' . $location
            );
        }

        return $location;
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
        // php pins these to http/https already; setting them keeps that true whatever the runtime allows
        $curl->setProtocols(CURLPROTO_HTTP | CURLPROTO_HTTPS);
        $curl->setRedirectProtocols(CURLPROTO_HTTP | CURLPROTO_HTTPS);
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
