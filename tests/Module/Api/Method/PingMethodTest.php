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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Lib\ServerDetailsRetrieverInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\EnvironmentInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class PingMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    /** @var EnvironmentInterface|MockInterface|null */
    private MockInterface $environment;

    /** @var MockInterface|ServerDetailsRetrieverInterface|null */
    private MockInterface $serverDetailsRetriever;

    private ?PingMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory          = $this->mock(StreamFactoryInterface::class);
        $this->configContainer        = $this->mock(ConfigContainerInterface::class);
        $this->logger                 = $this->mock(LoggerInterface::class);
        $this->environment            = $this->mock(EnvironmentInterface::class);
        $this->serverDetailsRetriever = $this->mock(ServerDetailsRetrieverInterface::class);

        $this->subject = new PingMethod(
            $this->streamFactory,
            $this->configContainer,
            $this->logger,
            $this->environment,
            $this->serverDetailsRetriever
        );
    }

    public function testHandleReturnsOutputWithoutAuthentication(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $version           = '5.0.0';
        $serverVersion     = '1234';
        $clientIp          = '1.2.3.4';
        $result            = 'some-result';
        $serverDetails     = ['some' => 'details'];
        $sessionExpiryDate = 12345;

        $this->environment->shouldReceive('getClientIp')
            ->withNoArgs()
            ->once()
            ->andReturn($clientIp);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::VERSION)
            ->once()
            ->andReturn($serverVersion);

        $gatekeeper->shouldReceive('sessionExists')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $gatekeeper->shouldReceive('extendSession')
            ->withNoArgs()
            ->once();
        $gatekeeper->shouldReceive('getSessionExpiryDate')
            ->withNoArgs()
            ->once()
            ->andReturn($sessionExpiryDate);

        $this->serverDetailsRetriever->shouldReceive('retrieve')
            ->withNoArgs()
            ->once()
            ->andReturn($serverDetails);

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Ping Received from %s', $clientIp),
                [LegacyLogger::CONTEXT_TYPE => PingMethod::class]
            )
            ->once();

        $output->shouldReceive('dict')
            ->with(
                array_merge(
                    [
                        'server' => $serverVersion,
                        'version' => $version,
                        'compatible' => '350001',
                    ],
                    ['session_expire' => date('c', $sessionExpiryDate)],
                    $serverDetails
                )
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                []
            )
        );
    }
}
