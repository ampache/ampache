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

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Doctrine\DBAL\Connection;
use Mockery\MockInterface;

class ClipRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private ClipRepository $subject;

    public function setUp(): void
    {
        $this->database = $this->mock(Connection::class);

        $this->subject = new ClipRepository(
            $this->database
        );
    }

    public function testCollectGarbageDeletes(): void
    {
        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `clip` USING `clip` LEFT JOIN `video` ON `video`.`id` = `clip`.`id` WHERE `video`.`id` IS NULL'
            )
            ->once();

        $this->subject->collectGarbage();
    }

    public function testGetDataByIdReturnsEmptyArrayIfNothingWasFound(): void
    {
        $clipId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `clip` WHERE `id` = ?',
                [$clipId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($clipId)
        );
    }

    public function testGetDataByIdReturnsData(): void
    {
        $clipId = 666;
        $result = ['some-data'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `clip` WHERE `id` = ?',
                [$clipId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getDataById($clipId)
        );
    }

    public function testUpdateUpdates(): void
    {
        $clipId   = 666;
        $artistId = 42;
        $songId   = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `clip` SET `artist` = ?, `song` = ? WHERE `id` = ?',
                [$artistId, $songId, $clipId]
            )
            ->once();

        $this->subject->update($clipId, $artistId, $songId);
    }
}
