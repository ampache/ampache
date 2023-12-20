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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class WebFetcherTest extends TestCase
{
    use ConsecutiveParams;

    private ConfigContainerInterface&MockObject $config;

    private UtilityFactoryInterface&MockObject $utilityFactory;

    private LoggerInterface&MockObject $logger;

    private WebFetcher $subject;

    protected function setUp(): void
    {
        $this->config         = $this->createMock(ConfigContainerInterface::class);
        $this->utilityFactory = $this->createMock(UtilityFactoryInterface::class);
        $this->logger         = $this->createMock(LoggerInterface::class);

        $this->subject = new WebFetcher(
            $this->config,
            $this->utilityFactory,
            $this->logger
        );
    }

    public function testFetchFetchesWithProxy(): void
    {
        $uri       = 'some-uri';
        $result    = 'some-result';
        $version   = '4.5.6';
        $proxyUser = 'some-proxy-user';
        $proxyPass = 'some-proxy-pass';
        $proxyHost = 'some-proxy-host';
        $proxyPort = 'some-proxy-port';

        $curl = $this->createMock(Curl::class);

        $this->utilityFactory->expects(static::once())
            ->method('createCurl')
            ->willReturn($curl);

        $curl->expects(static::once())
            ->method('setFollowLocation');
        $curl->expects(static::once())
            ->method('setUserAgent')
            ->with(sprintf('Ampache/%s', $version));
        $curl->expects(static::once())
            ->method('setProxy')
            ->with($proxyHost, $proxyPort, $proxyUser, $proxyPass);
        $curl->expects(static::once())
            ->method('get')
            ->with($uri);
        $curl->expects(static::once())
            ->method('close');

        $this->config->expects(static::once())
            ->method('getVersion')
            ->willReturn($version);
        $this->config->expects(static::exactly(4))
            ->method('get')
            ->with(...self::withConsecutive(
                [ConfigurationKeyEnum::PROXY_HOST],
                [ConfigurationKeyEnum::PROXY_PORT],
                [ConfigurationKeyEnum::PROXY_USER],
                [ConfigurationKeyEnum::PROXY_PASS],
            ))
            ->willReturn($proxyHost, $proxyPort, $proxyUser, $proxyPass);

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Fetching url: %s', $uri),
                [LegacyLogger::CONTEXT_TYPE => WebFetcher::class]
            );

        $curl->rawResponse = $result;

        static::assertSame(
            $result,
            $this->subject->fetch($uri)
        );
    }

    public function testFetchFails(): void
    {
        $uri       = 'some-uri';
        $version   = '4.5.6';
        $proxyHost = 'some-proxy-host';
        $proxyPort = 'some-proxy-port';

        $curl = $this->createMock(Curl::class);

        static::expectException(FetchFailedException::class);
        static::expectExceptionMessage(sprintf('Error fetching url: %s', $uri));

        $this->utilityFactory->expects(static::once())
            ->method('createCurl')
            ->willReturn($curl);

        $curl->expects(static::once())
            ->method('setFollowLocation');
        $curl->expects(static::once())
            ->method('setUserAgent')
            ->with(sprintf('Ampache/%s', $version));
        $curl->expects(static::once())
            ->method('setProxy')
            ->with($proxyHost, $proxyPort, null, null);
        $curl->expects(static::once())
            ->method('setTimeout')
            ->with(300);
        $curl->expects(static::once())
            ->method('get')
            ->with($uri);
        $curl->expects(static::once())
            ->method('close');

        $this->config->expects(static::once())
            ->method('getVersion')
            ->willReturn($version);
        $this->config->expects(static::exactly(4))
            ->method('get')
            ->with(...self::withConsecutive(
                [ConfigurationKeyEnum::PROXY_HOST],
                [ConfigurationKeyEnum::PROXY_PORT],
                [ConfigurationKeyEnum::PROXY_USER],
                [ConfigurationKeyEnum::PROXY_PASS],
            ))
            ->willReturn($proxyHost, $proxyPort, '', '');

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Fetching url: %s', $uri),
                [LegacyLogger::CONTEXT_TYPE => WebFetcher::class]
            );

        $curl->error = true;

        $this->subject->fetch($uri);
    }

    public function testFetchToFileThrowsIfFetchErrors(): void
    {
        $uri                 = 'some-uri';
        $destinationFilePath = 'some-path';
        $version             = '1.2.3';

        $curl = $this->createMock(Curl::class);

        static::expectException(FetchFailedException::class);
        static::expectExceptionMessage(sprintf('Error downloading to file: %s', $destinationFilePath));

        $this->config->expects(static::once())
            ->method('getVersion')
            ->willReturn($version);

        $this->utilityFactory->expects(static::once())
            ->method('createCurl')
            ->willReturn($curl);

        $curl->expects(static::once())
            ->method('setFollowLocation');
        $curl->expects(static::once())
            ->method('setUserAgent')
            ->with(sprintf('Ampache/%s', $version));
        $curl->expects(static::once())
            ->method('setReferer')
            ->with($uri);
        $curl->expects(static::once())
            ->method('download')
            ->with($uri, $destinationFilePath)
            ->willReturn(false);
        $curl->expects(static::once())
            ->method('close');

        $this->subject->fetchToFile($uri, $destinationFilePath);
    }

    public function testFetchToFileDownloads(): void
    {
        $uri                 = 'some-uri';
        $destinationFilePath = 'some-path';
        $version             = '1.2.3';

        $curl = $this->createMock(Curl::class);

        $this->config->expects(static::once())
            ->method('getVersion')
            ->willReturn($version);

        $this->utilityFactory->expects(static::once())
            ->method('createCurl')
            ->willReturn($curl);

        $curl->expects(static::once())
            ->method('setFollowLocation');
        $curl->expects(static::once())
            ->method('setUserAgent')
            ->with(sprintf('Ampache/%s', $version));
        $curl->expects(static::once())
            ->method('setReferer')
            ->with($uri);
        $curl->expects(static::once())
            ->method('download')
            ->with($uri, $destinationFilePath)
            ->willReturn(true);
        $curl->expects(static::once())
            ->method('close');

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Download to file completed: %s', $destinationFilePath),
                [LegacyLogger::CONTEXT_TYPE => WebFetcher::class]
            );

        $this->subject->fetchToFile($uri, $destinationFilePath);
    }
}
