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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\ModelFactoryInterface;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BroadcastRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private ModelFactoryInterface&MockObject $modelFactory;
    private BroadcastRepository $subject;

    public function testCreateInsertsAndReturnsTheNewId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, \'1\')',
                [42, 'some-name', 'some-description']
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        static::assertSame(
            666,
            $this->subject->create(42, 'some-name', 'some-description')
        );
    }

    public function testDeleteRemovesTheRow(): void
    {
        $broadcast = $this->createMock(Broadcast::class);

        $broadcast->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `broadcast` WHERE `id` = ?', [666]);

        $this->subject->delete($broadcast);
    }

    public function testFindByIdReturnsNullWhenTheBroadcastDoesNotExist(): void
    {
        $broadcast = $this->createMock(Broadcast::class);
        $broadcast->method('isNew')->willReturn(true);

        $this->modelFactory->method('createBroadcast')->willReturn($broadcast);

        static::assertNull($this->subject->findById(666));
    }

    public function testFindByIdReturnsTheLoadedBroadcast(): void
    {
        $broadcast = $this->createMock(Broadcast::class);
        $broadcast->method('isNew')->willReturn(false);

        $this->modelFactory->expects(static::once())
            ->method('createBroadcast')
            ->with(666)
            ->willReturn($broadcast);

        static::assertSame($broadcast, $this->subject->findById(666));
    }

    public function testFindByKeyReturnsNullIfTheKeyIsUnknown(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(false);

        $this->modelFactory->expects(static::never())
            ->method('createBroadcast');

        static::assertNull($this->subject->findByKey('some-key'));
    }

    public function testFindByKeyReturnsTheBroadcast(): void
    {
        $broadcast = $this->createMock(Broadcast::class);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `broadcast` WHERE `key` = ?', ['some-key'])
            ->willReturn('666');

        $this->modelFactory->expects(static::once())
            ->method('createBroadcast')
            ->with(666)
            ->willReturn($broadcast);

        static::assertSame(
            $broadcast,
            $this->subject->findByKey('some-key')
        );
    }

    public function testGetIdsByUserReturnsTheIds(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id` FROM `broadcast` WHERE `user` = ?', [42])
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls(1, 2, false);

        static::assertSame([1, 2], $this->subject->getIdsByUser(42));
    }

    public function testPersistInsertsABroadcastThatHasNoIdYet(): void
    {
        $broadcast              = new Broadcast(0);
        $broadcast->user        = 42;
        $broadcast->name        = 'some-name';
        $broadcast->description = 'some-description';

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, \'1\')',
                [42, 'some-name', 'some-description']
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        static::assertSame(666, $this->subject->persist($broadcast));
    }

    public function testUpdateListenersWritesTheCount(): void
    {
        $broadcast = $this->createMock(Broadcast::class);

        $broadcast->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `broadcast` SET `listeners` = ? WHERE `id` = ?', [12, 666]);

        $this->subject->updateListeners($broadcast, 12);
    }

    public function testUpdateSongWritesTheSongId(): void
    {
        $broadcast = $this->createMock(Broadcast::class);

        $broadcast->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `broadcast` SET `song` = ? WHERE `id` = ?', [33, 666]);

        $this->subject->updateSong($broadcast, 33);
    }

    public function testUpdateStateResetsTheSongAndListeners(): void
    {
        $broadcast = $this->createMock(Broadcast::class);

        $broadcast->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = \'0\', `listeners` = \'0\' WHERE `id` = ?',
                [1234, 'some-key', 666]
            );

        $this->subject->updateState($broadcast, 1234, 'some-key');
    }

    public function testUpdateWritesTheEditableProperties(): void
    {
        $broadcast = new Broadcast();

        $broadcast->id          = 666;
        $broadcast->name        = 'some-name';
        $broadcast->description = 'some-description';
        $broadcast->is_private  = true;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `broadcast` SET `name` = ?, `description` = ?, `is_private` = ? WHERE `id` = ?',
                ['some-name', 'some-description', 1, 666]
            );

        $this->subject->update($broadcast);
    }

    protected function setUp(): void
    {
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new BroadcastRepository(
            $this->modelFactory,
            $this->connection
        );
    }
}
