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

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class BookmarkRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private BookmarkRepository $subject;

    public function setUp(): void
    {
        $this->database = $this->mock(Connection::class);

        $this->subject = new BookmarkRepository(
            $this->database
        );
    }

    public function testGetBookmarksReturnsListOfIds(): void
    {
        $userId     = 666;
        $bookmarkId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `bookmark` WHERE `user` = ?',
                [$userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $bookmarkId, false);

        $this->assertSame(
            [$bookmarkId],
            $this->subject->getBookmarks($userId)
        );
    }

    public function testDeleteDeletes(): void
    {
        $bookmarkId = 666;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `bookmark` WHERE `id` = ?',
                [$bookmarkId]
            )
            ->once();

        $this->subject->delete($bookmarkId);
    }

    public function testCollectGarbageCollects(): void
    {
        $types = ['song', 'video', 'podcast_episode'];

        foreach ($types as $type) {
            $this->database->shouldReceive('executeQuery')
                ->with(
                    sprintf(
                        'DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `%s` ON `%s`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = \'%s\' AND `%s`.`id` IS NULL',
                        $type,
                        $type,
                        $type,
                        $type
                    )
                )
                ->once();
        }

        $this->subject->collectGarbage();
    }

    public function testUpdateUpdates(): void
    {
        $userId   = 666;
        $position = 42;
        $time     = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?',
                [$position, $time, $userId]
            )
            ->once();

        $this->subject->update($userId, $position, $time);
    }

    public function testCreateCreates(): void
    {
        $insertId   = 666;
        $position   = 21;
        $comment    = 'some-comment';
        $objectType = 'some-type';
        $objectId   = 33;
        $userId     = 111;
        $updateDate = 222;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `bookmark` (`user`, `position`, `comment`, `object_type`, `object_id`, `creation_date`, `update_date`) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$userId, $position, $comment, $objectType, $objectId, time(), $updateDate]
            )
            ->once();
        $this->database->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $insertId);

        $this->assertSame(
            $insertId,
            $this->subject->create(
                $position,
                $comment,
                $objectType,
                $objectId,
                $userId,
                $updateDate
            )
        );
    }

    public function testLookupSearchesWithoutCommentAndReturnsList(): void
    {
        $objectType = 'some-type';
        $objectId   = 666;
        $userId     = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `bookmark` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ?',
                [$userId, $objectType, $objectId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->lookup($objectType, $objectId, $userId)
        );
    }

    public function testLookupSearchesWithCommentAndReturnsList(): void
    {
        $objectType = 'some-type';
        $objectId   = 666;
        $userId     = 42;
        $bookmarkId = 33;
        $comment    = 'some-comment';

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `bookmark` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ? AND `comment` = ?',
                [$userId, $objectType, $objectId, $comment]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $bookmarkId, false);

        $this->assertSame(
            [$bookmarkId],
            $this->subject->lookup($objectType, $objectId, $userId, $comment)
        );
    }

    public function testEditUpdates(): void
    {
        $position   = 33;
        $comment    = 'come-comment';
        $objectType = 'some-type';
        $objectId   = 21;
        $userId     = 42;
        $updateDate = 12345;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `user` = ? AND `comment` = ? AND `object_type` = ? AND `object_id` = ?',
                [
                    $position,
                    $updateDate,
                    $userId,
                    $comment,
                    $objectType,
                    $objectId,
                ]
            )
            ->once();

        $this->subject->edit(
            $position,
            $comment,
            $objectType,
            $objectId,
            $userId,
            $updateDate
        );
    }

    public function testGetDataByIdReturnsEmptyArrayIfNothingWasFound(): void
    {
        $bookmarkId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `bookmark` WHERE `id`= ?',
                [$bookmarkId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($bookmarkId)
        );
    }

    public function testGetDataByIdReturnsData(): void
    {
        $bookmarkId = 666;
        $data       = ['some-data'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `bookmark` WHERE `id`= ?',
                [$bookmarkId]
            )
            ->once()
            ->andReturn($data);

        $this->assertSame(
            $data,
            $this->subject->getDataById($bookmarkId)
        );
    }
}
