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

namespace Ampache\Config\Init;

use Ampache\Config\Init\Exception\DatabaseOutdatedException;
use Ampache\MockeryTestCase;
use Ampache\Module\Util\EnvironmentInterface;
use Mockery\MockInterface;
use Override;
use RuntimeException;

class InitTest extends MockeryTestCase
{
    private MockInterface|EnvironmentInterface|null $environment;
    private MockInterface|InitializationHandlerInterface|null $initializationHandler;
    private Init $subject;

    public function testInitDoesNotSwallowUnmappedExceptions(): void
    {
        $error = new RuntimeException('some-error');

        $this->initializationHandler->shouldReceive('init')
            ->withNoArgs()
            ->once()
            ->andThrow($error);

        $this->environment->shouldReceive('isCli')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->expectExceptionObject($error);

        $this->subject->init();
    }

    public function testInitRethrowsMappedExceptionsOnCli(): void
    {
        $error = new DatabaseOutdatedException();

        $this->initializationHandler->shouldReceive('init')
            ->withNoArgs()
            ->once()
            ->andThrow($error);

        $this->environment->shouldReceive('isCli')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->expectExceptionObject($error);

        $this->subject->init();
    }

    public function testInitRunsTheHandlers(): void
    {
        $this->initializationHandler->shouldReceive('init')
            ->withNoArgs()
            ->once();

        $this->environment->shouldNotReceive('isCli');

        $this->subject->init();
    }

    #[Override]
    protected function setUp(): void
    {
        $this->environment           = $this->mock(EnvironmentInterface::class);
        $this->initializationHandler = $this->mock(InitializationHandlerInterface::class);

        $this->subject = new Init(
            $this->environment,
            [$this->initializationHandler]
        );
    }
}
