<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\Model\Shoutbox;
use DateTime;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class ShoutRepositoryTest extends TestCase
{
    use ConsecutiveParams;
    use RepositoryTestTrait;

    private DatabaseConnectionInterface&MockObject $connection;

    private LoggerInterface&MockObject $logger;

    private ShoutRepository $subject;

    protected function setUp(): void
    {
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger       = $this->createMock(LoggerInterface::class);

        $this->subject = new ShoutRepository(
            $this->connection,
            $this->logger,
        );
    }

    public function testGetByYieldsData(): void
    {
        $objectType = 'some-object-type';
        $objectId   = 42;

        $shoutBox  = $this->createMock(Shoutbox::class);
        $statement = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC',
                [$objectType, $objectId]
            )
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Shoutbox::class, [$this->subject]);
        $statement->expects(static::exactly(2))
            ->method('fetch')
            ->willReturn($shoutBox, false);

        static::assertSame(
            [$shoutBox],
            iterator_to_array($this->subject->getBy($objectType, $objectId))
        );
    }

    public function testFindByIdReturnsNullIfNotFound(): void
    {
        $shoutId = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `user_shout` WHERE `id` = ?',
                [$shoutId]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Shoutbox::class, [$this->subject]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        static::assertNull(
            $this->subject->findById($shoutId)
        );
    }

    public function testFindByIdReturnsShoutItem(): void
    {
        $shoutId = 666;

        $result = $this->createMock(PDOStatement::class);
        $shout  = $this->createMock(Shoutbox::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `user_shout` WHERE `id` = ?',
                [$shoutId]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Shoutbox::class, [$this->subject]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn($shout);

        static::assertSame(
            $shout,
            $this->subject->findById($shoutId)
        );
    }

    public function testDeleteDeletesItem(): void
    {
        $shout = $this->createMock(Shoutbox::class);

        $shoutBoxId = 666;

        $shout->expects(static::once())
            ->method('getId')
            ->willReturn($shoutBoxId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_shout` WHERE `id` = ?',
                [$shoutBoxId]
            );

        $this->subject->delete($shout);
    }

    public function testCollectGarbageDeletesDefaults(): void
    {
        $types = ['song', 'album', 'artist', 'label'];

        $query = <<<SQL
            DELETE FROM
                `user_shout`
            USING
                `user_shout`
            LEFT JOIN
                `%1\$s`
            ON
                `%1\$s`.`id` = `user_shout`.`object_id`
            WHERE
                `%1\$s`.`id` IS NULL
            AND
                `user_shout`.`object_type` = ?
        SQL;

        $params = [];

        foreach ($types as $type) {
            $params[] = [
                sprintf($query, $type),
                [$type]
            ];
        }

        $this->connection->expects(static::exactly(count($types)))
            ->method('query')
            ->with(...$this->withConsecutive(...$params));

        $this->subject->collectGarbage();
    }

    public function testCollectGarbageFailsWithUnsupportedType(): void
    {
        $this->logger->expects(static::once())
            ->method('critical')
            ->with('Garbage collect on type `snafu` is not supported.');

        $this->subject->collectGarbage('snafu');
    }

    public function testCollectGarbageDeletesDataForACertainType(): void
    {
        $type   = 'song';
        $typeId = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ?',
                [$type, $typeId]
            );

        $this->subject->collectGarbage($type, $typeId);
    }

    public function testPersistCreatesShout(): void
    {
        $shout = $this->createMock(Shoutbox::class);

        $shoutId    = 666;
        $userId     = 42;
        $date       = new DateTime();
        $text       = 'some-text';
        $sticky     = true;
        $objectId   = 123;
        $objectType = 'snafu';
        $offset     = 567;

        $shout->expects(static::once())
            ->method('isNew')
            ->willReturn(true);
        $shout->expects(static::once())
            ->method('getUserId')
            ->willReturn($userId);
        $shout->expects(static::once())
            ->method('getDate')
            ->willReturn($date);
        $shout->expects(static::once())
            ->method('getText')
            ->willReturn($text);
        $shout->expects(static::once())
            ->method('isSticky')
            ->willReturn($sticky);
        $shout->expects(static::once())
            ->method('getObjectId')
            ->willReturn($objectId);
        $shout->expects(static::once())
            ->method('getObjectType')
            ->willReturn($objectType);
        $shout->expects(static::once())
            ->method('getOffset')
            ->willReturn($offset);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `user_shout` (`user`, `date`, `text`, `sticky`, `object_id`, `object_type`, `data`) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $date->getTimestamp(),
                    $text,
                    (int) $sticky,
                    $objectId,
                    $objectType,
                    $offset
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($shoutId);

        static::assertSame(
            $shoutId,
            $this->subject->persist($shout)
        );
    }

    public function testPersistUpdatesShout(): void
    {
        $shout = $this->createMock(Shoutbox::class);

        $shoutId    = 666;
        $userId     = 42;
        $date       = new DateTime();
        $text       = 'some-text';
        $sticky     = true;
        $objectId   = 123;
        $objectType = 'snafu';
        $offset     = 567;

        $shout->expects(static::once())
            ->method('isNew')
            ->willReturn(false);
        $shout->expects(static::once())
            ->method('getId')
            ->willReturn($shoutId);
        $shout->expects(static::once())
            ->method('getUserId')
            ->willReturn($userId);
        $shout->expects(static::once())
            ->method('getDate')
            ->willReturn($date);
        $shout->expects(static::once())
            ->method('getText')
            ->willReturn($text);
        $shout->expects(static::once())
            ->method('isSticky')
            ->willReturn($sticky);
        $shout->expects(static::once())
            ->method('getObjectId')
            ->willReturn($objectId);
        $shout->expects(static::once())
            ->method('getObjectType')
            ->willReturn($objectType);
        $shout->expects(static::once())
            ->method('getOffset')
            ->willReturn($offset);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `user_shout` SET `user` = ?, `date` = ?, `text` = ?, `sticky` = ?, `object_id` = ?, `object_type` = ?, `data` = ? WHERE `id` = ?',
                [
                    $userId,
                    $date->getTimestamp(),
                    $text,
                    (int) $sticky,
                    $objectId,
                    $objectType,
                    $offset,
                    $shoutId
                ]
            );

        static::assertNull(
            $this->subject->persist($shout)
        );
    }

    public function testGetTopReturnsData(): void
    {
        $limit    = 1;
        $userName = 'some-username';

        $shout1 = $this->createMock(Shoutbox::class);
        $shout2 = $this->createMock(Shoutbox::class);

        $result1 = $this->createMock(PDOStatement::class);
        $result2 = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['SELECT * FROM `user_shout` WHERE `sticky` = 1 ORDER BY `date` DESC', []],
                    [
                        <<<SQL
                        SELECT
                            `user_shout`.*
                        FROM
                            `user_shout`
                        LEFT JOIN
                            `user`
                        ON
                            `user`.`id` = `user_shout`.`user`
                        WHERE
                            `user_shout`.`sticky` = 0 AND `user`.`username` = ?
                        ORDER BY
                            `user_shout`.`date` DESC
                        LIMIT 0
                        SQL,
                        [$userName]
                    ]
                )
            )
            ->willReturn($result1, $result2);

        $result1->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Shoutbox::class, [$this->subject]);
        $result1->expects(static::once())
            ->method('fetch')
            ->willReturn($shout1, false);

        $result2->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Shoutbox::class, [$this->subject]);
        $result2->expects(static::exactly(2))
            ->method('fetch')
            ->willReturn($shout2, false);

        static::assertSame(
            [$shout1, $shout2],
            iterator_to_array($this->subject->getTop($limit, $userName))
        );
    }

    public function testMigrateMigrates(): void
    {
        $objectType = 'some-object';
        $oldId      = 666;
        $newId      = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `user_shout` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newId, $objectType, $oldId]
            );

        $this->subject->migrate($objectType, $oldId, $newId);
    }

    public function testFindByIdPerformsTest(): void
    {
        $this->runFindByIdTrait(
            'user_shout',
            Shoutbox::class,
            [$this->subject]
        );
    }
}
