<?php

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

declare(strict_types=1);

namespace Ampache\Config\Init;

use Ampache\Config\Init\Exception\EnvironmentNotSuitableException;
use Ampache\MockeryTestCase;
use Ampache\Module\Util\EnvironmentInterface;
use Mockery\MockInterface;

class InitializationHandlerEnvironmentTest extends MockeryTestCase
{
    /** @var MockInterface|EnvironmentInterface|null */
    private MockInterface $environment;

    private InitializationHandlerEnvironment $subject;

    protected function setUp(): void
    {
        $this->environment = $this->mock(EnvironmentInterface::class);

        $this->subject = new InitializationHandlerEnvironment(
            $this->environment
        );
    }

    public function testInitThrowsExceptionIfEnvironmentNotSuitable(): void
    {
        $this->expectException(EnvironmentNotSuitableException::class);

        $this->environment->shouldReceive('check')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->init();
    }

    public function testInitPassesIfCheckSuceeds(): void
    {
        $this->environment->shouldReceive('check')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->init();
    }
}
