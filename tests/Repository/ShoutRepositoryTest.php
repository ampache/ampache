<?php

/*
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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Shoutbox;
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
}
