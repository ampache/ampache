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
 */

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Repository\WantedRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class WantedTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private WantedRepositoryInterface&MockObject $wantedRepository;

    public function testBuildCacheSkipsAnEmptyList(): void
    {
        $this->wantedRepository->expects(static::never())
            ->method('getRowsByIds');

        static::assertFalse(Wanted::build_cache([]));
    }

    public function testBuildCacheWarmsTheCacheFromTheRepository(): void
    {
        AmpConfig::set('memory_cache', true, true);

        $this->wantedRepository->expects(static::once())
            ->method('getRowsByIds')
            ->with([666])
            ->willReturn([['id' => 666, 'name' => 'some-album']]);

        static::assertTrue(Wanted::build_cache([666]));
    }

    protected function setUp(): void
    {
        $this->wantedRepository  = $this->createMock(WantedRepositoryInterface::class);
        $this->dic               = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(WantedRepositoryInterface::class)
            ->willReturn($this->wantedRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
