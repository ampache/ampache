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
 *
 */

namespace Ampache\Module\Application;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use DI\NotFoundException;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Narrowspark\HttpEmitter\AbstractSapiEmitter;
use Narrowspark\HttpEmitter\SapiEmitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ApplicationRunnerTest extends MockeryTestCase
{
    /** @var ContainerInterface|MockInterface|null */
    private ?MockInterface $dic;

    /** @var LoggerInterface|MockInterface|null */
    private ?MockInterface $logger;

    /** @var GatekeeperFactoryInterface|MockInterface|null */
    private ?MockInterface $gatekeeperFactory;

    /** @var MockInterface|UiInterface|null  */
    private ?MockInterface $ui;

    private ApplicationRunner $subject;

    protected function setUp(): void
    {
        $this->dic               = $this->mock(ContainerInterface::class);
        $this->logger            = $this->mock(LoggerInterface::class);
        $this->gatekeeperFactory = $this->mock(GatekeeperFactoryInterface::class);
        $this->ui                = $this->mock(UiInterface::class);

        $this->subject = new ApplicationRunner(
            $this->dic,
            $this->logger,
            $this->gatekeeperFactory,
            $this->ui
        );
    }

    public function testRunFailsWithMissingAction(): void
    {
        $request = $this->mock(ServerRequestInterface::class);

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([]);
        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->dic->shouldReceive('get')
            ->with('')
            ->once()
            ->andThrow(new NotFoundException());

        $this->logger->shouldReceive('critical')
            ->with(
                'No handler found for action ""',
                [LegacyLogger::CONTEXT_TYPE => ApplicationRunner::class]
            )
            ->once();

        $this->subject->run($request, [], '');
    }

    public function testRunFailsWithActionFromQueryParams(): void
    {
        $request = $this->mock(ServerRequestInterface::class);

        $action  = 'some-action';
        $handler = 'some-handler';

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([]);
        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['action' => $action]);

        $this->dic->shouldReceive('get')
            ->with($handler)
            ->once()
            ->andThrow(new NotFoundException());

        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('No handler found for action "%s"', $action),
                [LegacyLogger::CONTEXT_TYPE => ApplicationRunner::class]
            )
            ->once();

        $this->subject->run(
            $request,
            [$action => $handler],
            ''
        );
    }

    public function testRunRunsWithActionFromBody(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $handler    = $this->mock(ApplicationActionInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $emitter    = $this->mock(AbstractSapiEmitter::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $action       = 'some-action';
        $handler_name = 'some-handler';

        $emitter->shouldReceive('emit')
            ->with($response)
            ->once();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn(['action' => $action]);

        $this->dic->shouldReceive('get')
            ->with($handler_name)
            ->once()
            ->andReturn($handler);
        $this->dic->shouldReceive('get')
            ->with(SapiEmitter::class)
            ->once()
            ->andReturn($emitter);

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Found handler "%s" for action "%s"', $handler_name, $action),
                [LegacyLogger::CONTEXT_TYPE => ApplicationRunner::class]
            )
            ->once();

        $this->gatekeeperFactory->shouldReceive('createGuiGatekeeper')
            ->withNoArgs()
            ->once()
            ->andReturn($gatekeeper);

        $handler->shouldReceive('run')
            ->with($request, $gatekeeper)
            ->once()
            ->andReturn($response);

        $this->subject->run(
            $request,
            [$action => $handler_name],
            ''
        );
    }

    public function testRunRunsWithDefaultAction(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $handler    = $this->mock(ApplicationActionInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $emitter    = $this->mock(AbstractSapiEmitter::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $action         = 'some-action';
        $default_action = 'some-default-action';
        $handler_name   = 'some-handler';

        $emitter->shouldReceive('emit')
            ->with($response)
            ->once();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn(['action' => $action]);

        $this->dic->shouldReceive('get')
            ->with($handler_name)
            ->once()
            ->andReturn($handler);
        $this->dic->shouldReceive('get')
            ->with(SapiEmitter::class)
            ->once()
            ->andReturn($emitter);

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Found handler "%s" for action "%s"', $handler_name, $default_action),
                [LegacyLogger::CONTEXT_TYPE => ApplicationRunner::class]
            )
            ->once();

        $this->gatekeeperFactory->shouldReceive('createGuiGatekeeper')
            ->withNoArgs()
            ->once()
            ->andReturn($gatekeeper);

        $handler->shouldReceive('run')
            ->with($request, $gatekeeper)
            ->once()
            ->andReturn($response);

        $this->subject->run(
            $request,
            [$default_action => $handler_name],
            $default_action
        );
    }

    public function testRunCatchesDeniedException(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $handler    = $this->mock(ApplicationActionInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $action        = 'some-action';
        $handler_name  = 'some-handler';
        $error_message = 'some-error';

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn(['action' => $action]);

        $this->dic->shouldReceive('get')
            ->with($handler_name)
            ->once()
            ->andReturn($handler);

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Found handler "%s" for action "%s"', $handler_name, $action),
                [LegacyLogger::CONTEXT_TYPE => ApplicationRunner::class]
            )
            ->once();
        $this->logger->shouldReceive('warning')
            ->with(
                $error_message,
                Mockery::type('array')
            )
            ->once();

        $this->ui->shouldReceive('accessDenied')
            ->with($error_message)
            ->once();

        $this->gatekeeperFactory->shouldReceive('createGuiGatekeeper')
            ->withNoArgs()
            ->once()
            ->andReturn($gatekeeper);

        $handler->shouldReceive('run')
            ->with($request, $gatekeeper)
            ->once()
            ->andThrow(new AccessDeniedException($error_message));

        $this->subject->run(
            $request,
            [$action => $handler_name],
            ''
        );
    }

    public function testRunCatchesThrowable(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $handler    = $this->mock(ApplicationActionInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $action        = 'some-action';
        $handler_name  = 'some-handler';
        $error_message = 'some-error';
        $error         = new Exception($error_message);

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn(['action' => $action]);

        $this->dic->shouldReceive('get')
            ->with($handler_name)
            ->once()
            ->andReturn($handler);

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Found handler "%s" for action "%s"', $handler_name, $action),
                [LegacyLogger::CONTEXT_TYPE => ApplicationRunner::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                $error_message,
                [
                    LegacyLogger::CONTEXT_TYPE => sprintf(
                        '%s:%d',
                        $error->getFile(),
                        $error->getLine()
                    )
                ]
            )
            ->once();

        $this->gatekeeperFactory->shouldReceive('createGuiGatekeeper')
            ->withNoArgs()
            ->once()
            ->andReturn($gatekeeper);

        $handler->shouldReceive('run')
            ->with($request, $gatekeeper)
            ->once()
            ->andThrow($error);

        $this->subject->run(
            $request,
            [$action => $handler_name],
            ''
        );
    }
}
