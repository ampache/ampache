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

class CatalogFilterRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private CatalogFilterRepository $subject;

    public function testAddCatalogToGroupsEnablesOnlyTheDefaultGroup(): void
    {
        $result = $this->createMock(PDOStatement::class);
        $calls  = [];

        // the DEFAULT group really is id 0, so reading the ids must not use a fetchColumn() loop
        $result->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => '0'], ['id' => '3'], false);

        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$calls, $result): PDOStatement {
                $calls[] = $params;

                return $result;
            });

        $this->subject->addCatalogToGroups(7);

        self::assertSame([[], [0, 7, 1], [3, 7, 0]], $calls);
    }

    public function testCollectGarbageCarriesOnAfterAFailedStatement(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;
                if (count($calls) === 1) {
                    throw new QueryFailedException('nope');
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->logger->expects(static::once())
            ->method('debug');

        $this->subject->collectGarbage();

        self::assertStringContainsString("SET `id` = 0 WHERE `name` = 'DEFAULT'", $calls[2]);
    }

    public function testCreateGroupReturnsTheNewId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('INSERT INTO `catalog_filter_group` (`name`) VALUES (?)', ['some-filter']);

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(4);

        self::assertSame(4, $this->subject->createGroup('some-filter'));
    }

    public function testDeleteGroupRefusesTheDefaultGroup(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->deleteGroup(0));
    }

    public function testDeleteGroupReportsAFailedDelete(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('nope'));

        self::assertFalse($this->subject->deleteGroup(4));
    }

    public function testFindGroupsYieldsTheDefaultGroupAtIdZero(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `name` FROM `catalog_filter_group` ORDER BY `name` ')
            ->willReturn($result);

        $result->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => '0', 'name' => 'DEFAULT'], false);

        self::assertSame(
            [['id' => 0, 'name' => 'DEFAULT']],
            iterator_to_array($this->subject->findGroups())
        );
    }

    public function testGroupNameExistsDropsTheExclusionForANegativeId(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `catalog_filter_group` WHERE `name` = ?', ['DEFAULT'])
            ->willReturn('0');

        self::assertTrue($this->subject->groupNameExists('DEFAULT', -1));
    }

    public function testGroupNameExistsTreatsTheDefaultGroupAsFound(): void
    {
        // the DEFAULT group's id is 0, so an existence test may not lean on the value being truthy
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `catalog_filter_group` WHERE `name` = ? AND `id` != ?', ['DEFAULT', 4])
            ->willReturn('0');

        self::assertTrue($this->subject->groupNameExists('DEFAULT', 4));
    }

    public function testHasAccessAsksTheDefaultGroupForTheSystemUser(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_id` = ? AND `enabled` = 1 AND `group_id` = 0;',
                [7]
            )
            ->willReturn(false);

        self::assertFalse($this->subject->hasAccess(7, -1));
    }

    public function testHasAccessJoinsTheUsersGroupOtherwise(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_id` = ? AND `enabled` = 1 AND `group_id` IN (SELECT `catalog_filter_group` FROM `user` WHERE `id` = ?);',
                [7, 42]
            )
            ->willReturn('7');

        self::assertTrue($this->subject->hasAccess(7, 42));
    }

    public function testInsertCatalogsForGroupBindsEveryRow(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES (?, ?, ?),(?, ?, ?)',
                [4, 1, 1, 4, 3, 0]
            );

        self::assertTrue($this->subject->insertCatalogsForGroup(4, [1 => 1, 3 => 0]));
    }

    public function testInsertCatalogsForGroupSkipsTheStatementForAnEmptySet(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        self::assertTrue($this->subject->insertCatalogsForGroup(4, []));
    }

    public function testRepairDefaultGroupDoesNothingWhenItAlreadySitsAtZero(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->willReturn(['id' => '0', 'name' => 'DEFAULT']);

        $this->connection->expects(static::never())->method('query');

        self::assertFalse($this->subject->repairDefaultGroup());
    }

    public function testRepairDefaultGroupReseatsItAndBumpsTheAutoIncrement(): void
    {
        // autoincrement starts at 1, so a re-inserted group lands off id 0 and every catalog filter stops matching
        $this->connection->method('fetchRow')->willReturn(['id' => '3', 'name' => 'DEFAULT']);
        $this->connection->method('fetchOne')->willReturn('7');

        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ["INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');"],
                    ["UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';"],
                    ['ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = 8;'],
                )
            );

        self::assertTrue($this->subject->repairDefaultGroup());
    }

    public function testSetCatalogEnabledInsertsWhenTheMappingIsMissing(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `group_id` = ? AND `catalog_id` = ?',
                [4, 7]
            )
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `catalog_filter_group_map` SET `enabled` = ?, `group_id` = ?, `catalog_id` = ?',
                [1, 4, 7]
            );

        self::assertTrue($this->subject->setCatalogEnabled(4, 7, 1));
    }

    public function testSetCatalogEnabledUpdatesTheExistingMappingOfCatalogZero(): void
    {
        // catalog 0 is the orphan bucket and a real mapping target, so the probe must not test truthiness
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn('0');

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `catalog_filter_group_map` SET `enabled` = ? WHERE `group_id` = ? AND `catalog_id` = ?',
                [0, 4, 0]
            );

        self::assertTrue($this->subject->setCatalogEnabled(4, 0, 0));
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new CatalogFilterRepository(
            $this->connection,
            $this->logger
        );
    }
}
