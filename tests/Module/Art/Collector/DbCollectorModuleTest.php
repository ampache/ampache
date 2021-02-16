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

namespace Ampache\Module\Art\Collector;

use Ampache\Repository\Model\Art;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DbCollectorModuleTest extends MockeryTestCase
{
    /** @var DbCollectorModule|null */
    private ?DbCollectorModule $subject;

    public function setUp(): void
    {
        $this->subject = new DbCollectorModule();
    }

    public function testCollectsReturnsDbInfoIfHasInfo(): void
    {
        $art = Mockery::mock(Art::class);

        $art->shouldReceive('has_db_info')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        static::assertSame(
            ['db' => true],
            $this->subject->collect($art)
        );
    }

    public function testCollectsReturnsEmptyArrayIfInfoNotAvailable(): void
    {
        $art = Mockery::mock(Art::class);

        $art->shouldReceive('has_db_info')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        static::assertSame(
            [],
            $this->subject->collect($art)
        );
    }
}
