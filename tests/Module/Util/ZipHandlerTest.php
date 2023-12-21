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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ZipHandlerTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|LoggerInterface|null */
    private MockInterface $logger;

    private ?ZipHandler $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);

        $this->subject = new ZipHandler(
            $this->configContainer,
            $this->logger
        );
    }

    public function testIsZipableReturnsTrueIfSupported(): void
    {
        $type = 'foobar';

        $this->configContainer->shouldReceive('getTypesAllowedForZip')
            ->withNoArgs()
            ->once()
            ->andReturn([$type]);

        static::assertTrue(
            $this->subject->isZipable($type)
        );
    }

    public function testIsZipableReturnsFalseIfNotSupported(): void
    {
        $type = 'foobar';

        $this->configContainer->shouldReceive('getTypesAllowedForZip')
            ->withNoArgs()
            ->once()
            ->andReturn(['snoosnoo']);

        static::assertFalse(
            $this->subject->isZipable($type)
        );
    }
}
