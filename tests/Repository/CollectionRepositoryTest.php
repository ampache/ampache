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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class CollectionRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private CollectionRepository $subject;

    public function testAddItemAppendsAfterTheLastTrackNumber(): void
    {
        $collectionId = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT MAX(`track`) FROM `collection_map` WHERE `collection` = ?;',
                [$collectionId]
            )
            ->willReturn('3');

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    [
                        'INSERT INTO `collection_map` (`collection`, `object_id`, `object_type`, `track`) VALUES (?, ?, ?, ?);',
                        [$collectionId, 42, 'song', 4],
                    ],
                    [
                        'UPDATE `collection` SET `last_update` = ?, `last_count` = (SELECT COUNT(*) FROM `collection_map` WHERE `collection` = ?) WHERE `id` = ?;',
                        static::isType('array'),
                    ]
                )
            );

        static::assertTrue($this->subject->addItem($collectionId, 42, 'song'));
    }

    public function testAddItemRefusesADuplicateWhenUniquenessIsAsked(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `collection_map` WHERE `collection` = ? AND `object_type` = ? AND `object_id` = ?;',
                [666, 'song', 42]
            )
            ->willReturn('7');

        $this->connection->expects(static::never())
            ->method('query');

        static::assertFalse($this->subject->addItem(666, 42, 'song', true));
    }

    public function testCollectGarbageRunsTheRestOfTheSweepAfterAFailedStatement(): void
    {
        $calls = 0;

        $this->connection->expects(static::atLeast(2))
            ->method('query')
            ->willReturnCallback(function () use (&$calls): PDOStatement {
                $calls++;

                if ($calls === 1) {
                    throw new QueryFailedException();
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->logger->expects(static::once())
            ->method('debug');

        $this->subject->collectGarbage();

        static::assertGreaterThan(1, $calls);
    }

    public function testCreateReturnsNullWhenNothingWasInserted(): void
    {
        $user = $this->createMock(Model\User::class);

        $user->method('getId')
            ->willReturn(42);

        $this->connection->expects(static::once())
            ->method('query');
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(0);

        static::assertNull($this->subject->create('some-collection', $user));
    }

    public function testDeleteDropsTheMembersBeforeTheCollection(): void
    {
        $collectionId = 666;

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['DELETE FROM `collection_map` WHERE `collection` = ?;', [$collectionId]],
                    ['DELETE FROM `collection` WHERE `id` = ?;', [$collectionId]]
                )
            );

        $this->subject->delete($collectionId);
    }

    public function testGetItemsReturnsMembersInStoredOrder(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id`, `object_id`, `object_type`, `track` FROM `collection_map` WHERE `collection` = ? ORDER BY `track`, `id`;',
                [666]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(
                ['id' => '7', 'object_id' => '42', 'object_type' => 'song', 'track' => '1'],
                false
            );

        static::assertSame(
            [
                [
                    'id' => 7,
                    'object_id' => 42,
                    'object_type' => 'song',
                    'track' => 1,
                ],
            ],
            $this->subject->getItems(666)
        );
    }

    public function testObjectExistsRefusesATypeACollectionCannotHold(): void
    {
        $this->connection->expects(static::never())
            ->method('fetchOne');

        static::assertFalse($this->subject->objectExists('some-type', 42));
    }

    public function testRegenerateTrackNumbersRenumbersFromOne(): void
    {
        $collectionId = 666;
        $result       = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    [
                        'SELECT `id` FROM `collection_map` WHERE `collection` = ? ORDER BY `track`, `id`;',
                        [$collectionId],
                    ],
                    ['UPDATE `collection_map` SET `track` = ? WHERE `id` = ?;', [1, 9]],
                    ['UPDATE `collection_map` SET `track` = ? WHERE `id` = ?;', [2, 4]],
                    [
                        'UPDATE `collection` SET `last_update` = ?, `last_count` = (SELECT COUNT(*) FROM `collection_map` WHERE `collection` = ?) WHERE `id` = ?;',
                        static::isType('array'),
                    ]
                )
            )
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturn('9', '4', false);

        $this->subject->regenerateTrackNumbers($collectionId);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new CollectionRepository(
            $this->connection,
            $this->logger
        );
    }
}
