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
use Ampache\Repository\Model\BroadcastInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class BroadcastRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private MockInterface $modelFactory;

    private BroadcastRepository $subject;

    public function setUp(): void
    {
        $this->database     = $this->mock(Connection::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new BroadcastRepository(
            $this->database,
            $this->modelFactory
        );
    }

    public function testGetByUserReturnsListOfIds(): void
    {
        $userId      = 666;
        $broadcastId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `broadcast` WHERE `user` = ?',
                [$userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $broadcastId, false);

        $this->assertSame(
            [$broadcastId],
            $this->subject->getByUser($userId)
        );
    }

    public function testFindByKeyReturnsNullIfNothingWasFound(): void
    {
        $key = 'some-key';

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `broadcast` WHERE `key` = ?',
                [$key]
            )
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findByKey($key)
        );
    }

    public function testFindByKeyReturnsBroadcast(): void
    {
        $key         = 'some-key';
        $broadcastId = 666;

        $broadcast = $this->mock(BroadcastInterface::class);

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `broadcast` WHERE `key` = ?',
                [$key]
            )
            ->andReturn((string) $broadcastId);

        $this->modelFactory->shouldReceive('createBroadcast')
            ->with($broadcastId)
            ->once()
            ->andReturn($broadcast);

        $this->assertSame(
            $broadcast,
            $this->subject->findByKey($key)
        );
    }

    public function testDeleteDeletes(): void
    {
        $broadcast = $this->mock(BroadcastInterface::class);

        $broadcastId = 666;

        $broadcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($broadcastId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `broadcast` WHERE `id` = ?',
                [$broadcastId]
            )
            ->once();

        $this->subject->delete($broadcast);
    }

    public function testCreateCreatesAndReturnsInsertId(): void
    {
        $userId      = 666;
        $name        = 'some-name';
        $description = 'some-description';
        $broadcastId = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, ?)',
                [$userId, $name, $description, 1]
            )
            ->once();
        $this->database->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $broadcastId);

        $this->assertSame(
            $broadcastId,
            $this->subject->create($userId, $name, $description)
        );
    }

    public function testUpdateStateUpdates(): void
    {
        $broadcastId = 666;
        $started     = 42;
        $key         = 'some-key';

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = ?, `listeners` = ? WHERE `id` = ?',
                [$started, $key, 0, 0, $broadcastId]
            )
            ->once();

        $this->subject->updateState($broadcastId, $started, $key);
    }

    public function testUpdateListeners(): void
    {
        $broadcastId = 666;
        $listeners   = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `broadcast` SET `listeners` = ? WHERE `id` = ?',
                [$listeners, $broadcastId]
            )
            ->once();

        $this->subject->updateListeners($broadcastId, $listeners);
    }

    public function testUpdateSongUpdates(): void
    {
        $broadcastId = 666;
        $songId      = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `broadcast` SET `song` = ? WHERE `id` = ?',
                [$songId, $broadcastId]
            )
            ->once();

        $this->subject->updateSong($broadcastId, $songId);
    }

    public function testUpdateUpdates(): void
    {
        $broadcastId = 666;
        $name        = 'some-name';
        $description = 'some-description';
        $isPrivate   = 1;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `broadcast` SET `name` = ?, `description` = ?, `is_private` = ? WHERE `id` = ?',
                [$name, $description, $isPrivate, $broadcastId]
            )
            ->once();

        $this->subject->update(
            $broadcastId,
            $name,
            $description,
            $isPrivate
        );
    }

    public function testGetDataByIdReturnsEmptyArrayIfNothingWasFound(): void
    {
        $broadcastId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `broadcast` WHERE `id`= ?',
                [$broadcastId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($broadcastId)
        );
    }

    public function testGetDataByIdReturnsDataset(): void
    {
        $broadcastId = 666;
        $dataset     = ['some-data'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `broadcast` WHERE `id`= ?',
                [$broadcastId]
            )
            ->once()
            ->andReturn($dataset);

        $this->assertSame(
            $dataset,
            $this->subject->getDataById($broadcastId)
        );
    }
}
