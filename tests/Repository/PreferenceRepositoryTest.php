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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class PreferenceRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private PreferenceRepository $subject;

    public function testAddUserPreferenceIgnoresARowThatAlreadyExists(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT IGNORE INTO user_preference (`user`, `preference`, `name`, `value`) VALUES (?, ?, ?, ?)',
                [666, 42, 'some-pref', 'some-value']
            );

        $this->subject->addUserPreference(666, 42, 'some-pref', 'some-value');
    }

    public function testGetAllPreferencesDropsTheSystemRowsForARealUser(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with("SELECT * FROM `preference` WHERE `category` !='system';")
            ->willReturn($this->emptyResult());

        static::assertSame([], $this->subject->getAllPreferences(false));
    }

    public function testGetAllPreferencesKeepsThemForTheSystemUser(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT * FROM `preference`')
            ->willReturn($this->emptyResult());

        static::assertSame([], $this->subject->getAllPreferences(true));
    }

    public function testRepairLanguagePreferencesFallsBackToEnglish(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        $bound = [];
        $this->connection->method('query')->willReturnCallback(
            function (string $sql, array $params) use (&$bound): \PDOStatement {
                $bound[] = $params;

                return $this->createMock(\PDOStatement::class);
            }
        );

        $this->subject->repairLanguagePreferences();

        static::assertSame(['en_US'], $bound[1]);
    }

    public function testRepairLanguagePreferencesSeedsEveryoneFromTheSystemUser(): void
    {
        // the system row is corrected first precisely so its value is safe to hand to everybody else
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn('de_DE');

        $bound = [];
        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use (&$bound): \PDOStatement {
                $bound[] = $params;

                return $this->createMock(\PDOStatement::class);
            });

        $this->subject->repairLanguagePreferences();

        static::assertSame([[], ['de_DE']], $bound);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new PreferenceRepository(
            $this->connection,
            $this->logger
        );
    }

    private function emptyResult(): \PDOStatement&MockObject
    {
        $result = $this->createMock(\PDOStatement::class);
        $result->method('fetch')->willReturn(false);

        return $result;
    }
}
