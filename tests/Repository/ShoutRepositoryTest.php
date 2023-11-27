<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use DateTime;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class ShoutRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;

    private LoggerInterface&MockObject $logger;

    private ModelFactoryInterface&MockObject $modelFactory;

    private ShoutRepository $subject;

    protected function setUp(): void
    {
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger       = $this->createMock(LoggerInterface::class);
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);

        $this->subject = new ShoutRepository(
            $this->connection,
            $this->logger,
            $this->modelFactory
        );
    }

    public function testGetByYieldsData(): void
    {
        $objectType = 'some-object-type';
        $objectId   = 42;
        $shoutBoxId = 666;

        $shoutBox  = $this->createMock(Shoutbox::class);
        $statement = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC',
                [$objectType, $objectId]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $shoutBoxId, false);

        $this->modelFactory->expects(static::once())
            ->method('createShoutbox')
            ->with($shoutBoxId)
            ->willReturn($shoutBox);

        static::assertSame(
            [$shoutBox],
            iterator_to_array($this->subject->getBy($objectType, $objectId))
        );
    }

    public function testDeleteDeletesItem(): void
    {
        $shoutBoxId = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_shout` WHERE `id` = ?',
                [$shoutBoxId]
            );

        $this->subject->delete($shoutBoxId);
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

    public function testUpdateUpdates(): void
    {
        $shout = $this->createMock(Shoutbox::class);

        $comment = 'some-comment';
        $shoutId = 666;

        $shout->expects(static::once())
            ->method('getId')
            ->willReturn($shoutId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `user_shout` SET `text` = ?, `sticky` = ? WHERE `id` = ?',
                [
                    $comment,
                    1,
                    $shoutId
                ]
            );

        $this->subject->update(
            $shout,
            [
                'comment' => $comment,
                'sticky' => true
            ]
        );
    }

    public function testCreateCreatesNewShoutItem(): void
    {
        $user    = $this->createMock(User::class);
        $libitem = $this->createMock(library_item::class);

        $date       = new DateTime();
        $text       = 'some-text';
        $isSticky   = true;
        $objectType = 'some-object';
        $offset     = 666;
        $insertedId = 42;
        $userId     = 21;
        $libitemId  = 33;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $libitem->expects(static::once())
            ->method('getId')
            ->willReturn($libitemId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `user_shout` (`user`, `date`, `text`, `sticky`, `object_id`, `object_type`, `data`) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $date->getTimestamp(),
                    $text,
                    (int) $isSticky,
                    $libitemId,
                    $objectType,
                    $offset
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($insertedId);

        static::assertSame(
            $insertedId,
            $this->subject->create(
                $user,
                $date,
                $text,
                $isSticky,
                $libitem,
                $objectType,
                $offset
            )
        );
    }

    public function testGetTopReturnsData(): void
    {
        $limit    = 1;
        $userName = 'some-username';
        $shoutId1 = 666;
        $shoutId2 = 42;

        $shout1 = $this->createMock(Shoutbox::class);
        $shout2 = $this->createMock(Shoutbox::class);

        $result1 = $this->createMock(PDOStatement::class);
        $result2 = $this->createMock(PDOStatement::class);

        $this->modelFactory->expects(static::exactly(2))
            ->method('createShoutbox')
            ->with(...self::withConsecutive([$shoutId1], [$shoutId2]))
            ->willReturn($shout1, $shout2);

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['SELECT id FROM `user_shout` WHERE `sticky` = 1 ORDER BY `date` DESC', []],
                    [
                        <<<SQL
                        SELECT
                            `user_shout`.`id` AS `id`
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
            ->method('fetchColumn')
            ->willReturn((string) $shoutId1);

        $result2->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $shoutId2, false);

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
}
