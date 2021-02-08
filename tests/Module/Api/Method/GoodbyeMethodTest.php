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
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\EnvironmentInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class GoodbyeMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    /** @var EnvironmentInterface|MockInterface|null */
    private MockInterface $environment;

    private ?GoodbyeMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->logger        = $this->mock(LoggerInterface::class);
        $this->environment   = $this->mock(EnvironmentInterface::class);

        $this->subject = new GoodbyeMethod(
            $this->streamFactory,
            $this->logger,
            $this->environment
        );
    }

    public function testHandleThrowsExceptionIfAuthParameterIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'auth'));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfSessionDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'auth'));

        $gatekeeper->shouldReceive('sessionExists')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['auth' => 'some-auth']
        );
    }

    public function testHandleEndsSession(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $result    = 'some-result';
        $clientIp  = '1.2.3.4';
        $authValue = 'some-auth';

        $gatekeeper->shouldReceive('sessionExists')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $gatekeeper->shouldReceive('endSession')
            ->withNoArgs()
            ->once();

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Goodbye Received from %s', $clientIp),
                [LegacyLogger::CONTEXT_TYPE => GoodbyeMethod::class]
            )
            ->once();

        $output->shouldReceive('success')
            ->with($authValue)
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

        $this->environment->shouldReceive('getClientIp')
            ->withNoArgs()
            ->once()
            ->andReturn($clientIp);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['auth' => $authValue]
            )
        );
    }
}
