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
use Doctrine\DBAL\Result;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ShoutRepositoryTest extends MockeryTestCase
{
    /** @var Connection|MockInterface */
    private MockInterface $connection;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    private ShoutRepository $subject;

    public function setUp(): void
    {
        $this->connection = $this->mock(Connection::class);
        $this->logger     = $this->mock(LoggerInterface::class);

        $this->subject = new ShoutRepository(
            $this->connection,
            $this->logger
        );
    }

    public function testGetByIdReturnListOfIds(): void
    {
        $objectType = 'some-object-type';
        $objectId   = 42;
        $licenseId  = 666;

        $result = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC',
                [$objectType, $objectId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $licenseId, false);

        $this->assertSame(
            [$licenseId],
            $this->subject->getBy($objectType, $objectId)
        );
    }

    public function testDeleteDeletes(): void
    {
        $shoutId = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `user_shout` WHERE `id` = ?',
                [$shoutId]
            )
            ->once();

        $this->subject->delete($shoutId);
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-object-type';
        $oldObjectId = 42;
        $newObjectId = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `user_shout` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newObjectId, $objectType, $oldObjectId]
            )
            ->once();

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }

    public function testInsertAddNewEntry(): void
    {
        $userId     = 666;
        $date       = 123456;
        $comment    = 'some-comment';
        $sticky     = 1;
        $objectId   = 42;
        $objectType = 'some-object-type';
        $data       = 'some-data';
        $shoutId    = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `user_shout` (`user`, `date`, `text`, `sticky`, `object_id`, `object_type`, `data`) VALUES (? , ?, ?, ?, ?, ?, ?)',
                [$userId, $date, $comment, $sticky, $objectId, $objectType, $data]
            )
            ->once();
        $this->connection->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((int) $shoutId);

        $this->subject->insert(
            $userId,
            $date,
            $comment,
            $sticky,
            $objectId,
            $objectType,
            $data
        );
    }

    public function testUpdateUpdates(): void
    {
        $shoutId  = 666;
        $comment  = 'some-comment';
        $isSticky = true;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `user_shout` SET `text` = ?, `sticky` = ? WHERE `id` = ?',
                [$comment, (int) $isSticky, $shoutId]
            )
            ->once();

        $this->subject->update(
            $shoutId,
            $comment,
            $isSticky
        );
    }

    public function testGetDataByIdReturnsEmptyArrayIfNotFound(): void
    {
        $shoutId = 666;

        $this->connection->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `user_shout` WHERE `id` = ?',
                [$shoutId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($shoutId)
        );
    }

    public function testGetDataByIdReturnsResult(): void
    {
        $shoutId = 666;

        $result = ['some' => 'result'];

        $this->connection->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `user_shout` WHERE `id` = ?',
                [$shoutId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getDataById($shoutId)
        );
    }
}
