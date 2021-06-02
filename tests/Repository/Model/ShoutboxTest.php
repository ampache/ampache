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

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use Ampache\Repository\ShoutRepositoryInterface;
use Mockery\MockInterface;

class ShoutboxTest extends MockeryTestCase
{
    private int $id = 666;

    private MockInterface $shoutRepository;

    private Shoutbox $subject;

    public function setUp(): void
    {
        $this->shoutRepository = $this->mock(ShoutRepositoryInterface::class);

        $this->subject = new Shoutbox(
            $this->shoutRepository,
            $this->id
        );
    }

    public function testIsNewReturnsTrueIfDataIsEmpty(): void
    {
        $this->shoutRepository->shouldReceive('getDataById')
            ->with($this->id)
            ->once()
            ->andReturn([]);

        $this->assertTrue(
            $this->subject->isNew()
        );
    }

    /**
     * @dataProvider dbDataDataProvider
     */
    public function testDataFromDb(
        string $methodName,
        string $dbColumnName,
        $value
    ): void {
        $this->shoutRepository->shouldReceive('getDataById')
            ->with($this->id)
            ->once()
            ->andReturn([$dbColumnName => $value]);

        $this->assertSame(
            $value,
            call_user_func([$this->subject, $methodName])
        );
    }

    public function dbDataDataProvider(): array
    {
        return [
            ['getId', 'id', 666],
            ['getObjectType', 'object_type', 'some-type'],
            ['getObjectId', 'object_id', 42],
            ['getUserId', 'user', 33],
            ['getSticky', 'sticky', 1],
            ['getText', 'text', 'some-text'],
            ['getData', 'data', 'some-data'],
            ['getDate', 'date', 21],
        ];
    }

    public function testGetStickyFormattedReturnsNoIfNotSticky(): void
    {
        $this->shoutRepository->shouldReceive('getDataById')
            ->with($this->id)
            ->once()
            ->andReturn(['sticky' => 0]);

        $this->assertSame(
            'No',
            $this->subject->getStickyFormatted()
        );
    }

    public function testGetStickyFormattedReturnsYesIfSticky(): void
    {
        $this->shoutRepository->shouldReceive('getDataById')
            ->with($this->id)
            ->once()
            ->andReturn(['sticky' => 1]);

        $this->assertSame(
            'Yes',
            $this->subject->getStickyFormatted()
        );
    }

    public function testGetTextFormattedReturnsValue(): void
    {
        $this->shoutRepository->shouldReceive('getDataById')
            ->with($this->id)
            ->once()
            ->andReturn(['text' => "foo\r\nbar\nbaz\rfoo"]);

        $this->assertSame(
            'foo<br />bar<br />baz<br />foo',
            $this->subject->getTextFormatted()
        );
    }
}
