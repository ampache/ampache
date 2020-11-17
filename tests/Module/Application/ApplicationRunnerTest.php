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

namespace Ampache\Module\Application;

use Ampache\MockeryTestCase;
use Ampache\Module\System\LegacyLogger;
use DI\NotFoundException;
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
    private ContainerInterface $dic;

    /** @var LoggerInterface|MockInterface|null */
    private LoggerInterface $logger;
    
    /** @var ApplicationRunner|null */
    private ApplicationRunner $subject;
    
    public function setUp(): void
    {
        $this->dic    = Mockery::mock(ContainerInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        
        $this->subject = new ApplicationRunner(
            $this->dic,
            $this->logger
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
                'No handler found for action ``',
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
                sprintf('No handler found for action `%s`', $action),
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
        $request  = $this->mock(ServerRequestInterface::class);
        $handler  = $this->mock(ApplicationActionInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $emitter  = $this->mock(AbstractSapiEmitter::class);

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
                sprintf('Found handler `%s` for action `%s`', $handler_name, $action),
                [LegacyLogger::CONTEXT_TYPE => ApplicationRunner::class]
            )
            ->once();
        
        $handler->shouldReceive('run')
            ->with($request)
            ->once()
            ->andReturn($response);

        $this->subject->run(
            $request,
            [$action => $handler_name],
            ''
        );
    }
}
