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

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use Ampache\Repository\MetadataRepositoryInterface;
use Mockery\MockInterface;

class MetadataFieldTest extends MockeryTestCase
{
    private MockInterface $metadataRepository;

    private MetadataField $subject;

    private int $id = 666;

    public function setUp(): void
    {
        $this->metadataRepository = $this->mock(MetadataRepositoryInterface::class);

        $this->subject = new MetadataField(
            $this->metadataRepository,
            $this->id
        );
    }

    /**
     * @dataProvider getterDataProvider
     */
    public function testGetter(
        string $methodName,
        string $key,
        string $value,
        $expectedValue
    ): void {
        $this->metadataRepository->shouldReceive('getFieldDbData')
            ->with($this->id)
            ->once()
            ->andReturn([$key => $value]);

        $this->assertSame(
            $expectedValue,
            call_user_func([$this->subject, $methodName])
        );
    }

    public function getterDataProvider(): array
    {
        return [
            ['getId', 'id', '666', 666],
            ['getName', 'name', 'some-name', 'some-name'],
            ['getPublic', 'public', '42', 42],
        ];
    }

    public function testGetFormattedNameReturnsValue(): void
    {
        $name = 'some_name';

        $this->metadataRepository->shouldReceive('getFieldDbData')
            ->with($this->id)
            ->once()
            ->andReturn(['name' => $name]);

        $this->assertSame(
            ucwords(str_replace("_", " ", $name)),
            $this->subject->getFormattedName()
        );
    }

    public function testIsNewReturnsTrueIfDataIsEmpty(): void
    {
        $this->metadataRepository->shouldReceive('getFieldDbData')
            ->with($this->id)
            ->once()
            ->andReturn([]);

        $this->assertTrue(
            $this->subject->isNew()
        );
    }

    public function testIsNewReturnsFalseIfDataExists(): void
    {
        $this->metadataRepository->shouldReceive('getFieldDbData')
            ->with($this->id)
            ->once()
            ->andReturn(['some' => '4data']);

        $this->assertFalse(
            $this->subject->isNew()
        );
    }
}
