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

namespace Ampache\Module\Catalog\Process;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Admin\Catalog\AddToAllCatalogsAction;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;

class CatalogProcessTypeMapperTest extends MockeryTestCase
{
    /** @var MockInterface|ContainerInterface|null */
    private MockInterface $dic;

    private CatalogProcessTypeMapper $subject;

    public function setUp(): void
    {
        $this->dic = $this->mock(ContainerInterface::class);

        $this->subject = new CatalogProcessTypeMapper(
            $this->dic
        );
    }

    public function testMapReturnsNullIfProcessTypeIsUnknown(): void
    {
        $this->assertNull(
            $this->subject->map('foobar')
        );
    }

    public function testMapReturnsNullIfClassnameIsUnknown(): void
    {
        $this->dic->shouldReceive('has')
            ->with(AddToCatalogProcess::class)
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->map(CatalogProcessTypeEnum::ADD)
        );
    }

    public function testMapReturnsMappedClass(): void
    {
        $class = $this->mock(CatalogProcessInterface::class);

        $this->dic->shouldReceive('has')
            ->with(AddToCatalogProcess::class)
            ->once()
            ->andReturnTrue();
        $this->dic->shouldReceive('get')
            ->with(AddToCatalogProcess::class)
            ->once()
            ->andReturn($class);

        $this->assertSame(
            $class,
            $this->subject->map(CatalogProcessTypeEnum::ADD)
        );
    }
}
