<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class UpdateInfoRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;

    private UpdateInfoRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new UpdateInfoRepository(
            $this->connection,
        );
    }

    public function testGetValeByKeyReturnsNullIfNothingWasFound(): void
    {
        $key = 'some-key';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT value from update_info WHERE `key` = ? LIMIT 1',
                [$key],
            )
            ->willReturn(false);

        static::assertNull(
            $this->subject->getValueByKey($key),
        );
    }

    public function testGetValeByKeyReturnsValue(): void
    {
        $key   = 'some-key';
        $value = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT value from update_info WHERE `key` = ? LIMIT 1',
                [$key],
            )
            ->willReturn($value);

        static::assertSame(
            (string) $value,
            $this->subject->getValueByKey($key),
        );
    }

    public function testSetValueUpdatesExistingValue(): void
    {
        $key   = 'some-key';
        $value = 'some-value';

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `update_info` SET `value` = ? WHERE `key` = ?',
                [$value, $key]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('rowCount')
            ->willReturn(1);

        $this->subject->setValue($key, $value);
    }

    public function testSetValueInsertIfUpdateFails(): void
    {
        $key   = 'some-key';
        $value = 'some-value';

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    [
                        'UPDATE `update_info` SET `value` = ? WHERE `key` = ?',
                        [$value, $key]
                    ],
                    [
                        'INSERT INTO `update_info` (`key`, `value`) VALUES (?, ?)',
                        [$key, $value]
                    ]
                )
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('rowCount')
            ->willReturn(0);

        $this->subject->setValue($key, $value);
    }
}
