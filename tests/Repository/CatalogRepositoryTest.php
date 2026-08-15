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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Catalog\CatalogTypeEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\InsertIdInvalidException;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\CatalogFieldEnum;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueError;

class CatalogRepositoryTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DatabaseConnectionInterface&MockObject $connection;
    private CatalogRepository $subject;

    public function testCreateSubTypeTableRefusesAColumnNoBackendDeclares(): void
    {
        $this->configContainer->method('get')->willReturn('utf8mb4');

        $this->connection->expects(static::never())
            ->method('query');

        static::expectException(ValueError::class);

        $this->subject->createSubTypeTable(CatalogTypeEnum::LOCAL, ['DROP TABLE `song`' => 'INT']);
    }

    public function testCreateSubTypeTableWrapsTheBackendsColumnsAndCollatesTheStrings(): void
    {
        $this->configContainer->method('get')->willReturnCallback(static fn(string $key): string => match ($key) {
            'database_collation' => 'utf8mb4_unicode_ci',
            'database_charset' => 'utf8mb4',
            'database_engine' => 'InnoDB',
            default => '',
        });

        $this->connection->expects(static::once())
            ->method('query')
            ->with('CREATE TABLE `catalog_dropbox` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `apikey` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, `getchunk` TINYINT(1) NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->subject->createSubTypeTable(CatalogTypeEnum::DROPBOX, ['apikey' => 'VARCHAR(255)', 'getchunk' => 'TINYINT(1)']);
    }

    public function testDeleteSubTypeRowNamesTheBackendsOwnTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `catalog_seafile` WHERE `catalog_id` = ?', [7]);

        static::assertTrue($this->subject->deleteSubTypeRow(CatalogTypeEnum::SEAFILE, 7));
    }

    public function testDeleteSubTypeRowReportsAFailure(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('nope'));

        static::assertFalse($this->subject->deleteSubTypeRow(CatalogTypeEnum::LOCAL, 7));
    }

    public function testDropSubTypeTableToleratesATableThatIsAlreadyGone(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DROP TABLE `catalog_subsonic`')
            ->willThrowException(new QueryFailedException('no such table'));

        $this->subject->dropSubTypeTable(CatalogTypeEnum::SUBSONIC);
    }

    public function testFindEnabledSeparatesADisabledCatalogFromAMissingOne(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with('SELECT `enabled` FROM `catalog` WHERE `id` = ?', [7])
            ->willReturn('0', false);

        static::assertFalse($this->subject->findEnabled(7));
        static::assertNull($this->subject->findEnabled(7));
    }

    public function testFindNameFallsBackToAnEmptyString(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `name` FROM `catalog` WHERE `id` = ?', [666])
            ->willReturn(false);

        static::assertSame('', $this->subject->findName(666));
    }

    public function testFindSubTypeIdReturnsNullWhenTheBackendsTableIsGone(): void
    {
        // uninstalling a backend drops its settings table but can leave catalog rows pointing at it
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willThrowException(new QueryFailedException('no such table'));

        static::assertNull($this->subject->findSubTypeId(CatalogTypeEnum::SUBSONIC, 7));
    }

    public function testFindTypeReturnsNullForAMissingCatalog(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `catalog_type` FROM `catalog` WHERE `id` = ?', [666])
            ->willReturn(false);

        static::assertNull($this->subject->findType(666));
    }

    public function testGetIdsAppliesNoWhereClauseWithoutNarrowing(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id` FROM `catalog` ORDER BY `name`;', [])
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('3', false);

        static::assertSame([3], $this->subject->getIds());
    }

    public function testGetIdsHoldsTheSystemUserToTheDefaultFilterGroup(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                static::stringContains('`catalog_filter_group_map`.`group_id` = 0'),
                []
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        static::assertSame([], $this->subject->getIds(null, false, -1));
    }

    public function testGetIdsIgnoresTheFilterForUserZero(): void
    {
        $result = $this->createMock(PDOStatement::class);

        // a user id of 0 means "no user", and never filtered in the version this replaced
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id` FROM `catalog` ORDER BY `name`;', [])
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        static::assertSame([], $this->subject->getIds(null, false, 0));
    }

    public function testGetIdsJoinsTheUsersOwnFilterGroup(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                static::stringContains('INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id`'),
                [42]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        static::assertSame([], $this->subject->getIds(null, false, 42));
    }

    public function testGetIdsStacksTheGatherTypeAndEnabledNarrowing(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `catalog` WHERE `gather_types` = ? AND `enabled` = 1 ORDER BY `name`;',
                ['music']
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        static::assertSame([], $this->subject->getIds('music', true));
    }

    public function testInsertReturnsZeroWhenTheRowProducedNoId(): void
    {
        $this->connection->expects(static::once())
            ->method('query');

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willThrowException(new InsertIdInvalidException());

        static::assertSame(0, $this->subject->insert('some-catalog', 'local', '', '', 'music'));
    }

    public function testInsertSubTypeAppendsTheCatalogIdAndBindsEveryValue(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `catalog_remote` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)',
                ['http://host', 'user', 'pass', 7]
            );

        static::assertTrue($this->subject->insertSubType(
            CatalogTypeEnum::REMOTE,
            ['uri' => 'http://host', 'username' => 'user', 'password' => 'pass'],
            7
        ));
    }

    public function testInsertWritesTheRowAndReturnsItsId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `catalog` (`name`, `catalog_type`, `rename_pattern`, `sort_pattern`, `gather_types`) VALUES (?, ?, ?, ?, ?)',
                ['some-catalog', 'local', '%T', '%a', 'music']
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(4);

        static::assertSame(4, $this->subject->insert('some-catalog', 'local', '%T', '%a', 'music'));
    }

    public function testIsActionProcessingReflectsWhetherAnyConnectionHoldsTheLock(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with('SELECT IS_USED_LOCK(?)', ['ampache_sse_action_some-key'])
            ->willReturn('42', null);

        static::assertTrue($this->subject->isActionProcessing('some-key'));
        static::assertFalse($this->subject->isActionProcessing('some-key'));
    }

    public function testReleaseActionLockCallsReleaseLockWithTheNamespacedName(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT RELEASE_LOCK(?)', ['ampache_sse_action_some-key']);

        $this->subject->releaseActionLock('some-key');
    }

    public function testReleaseProcessingLockCallsReleaseLockWithTheNamespacedName(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT RELEASE_LOCK(?)', ['ampache_catalog_7']);

        $this->subject->releaseProcessingLock(7);
    }

    public function testSetFieldNamesOnlyABoundedColumn(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `catalog` SET `last_update` = ? WHERE `id` = ?', [123456, 7]);

        static::assertTrue($this->subject->setField(7, CatalogFieldEnum::LAST_UPDATE, 123456));
    }

    public function testSubTypeTableExistsAsksForTheBackendsOwnTable(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with("SHOW TABLES LIKE 'catalog_beetsremote'")
            ->willReturn(false);

        static::assertFalse($this->subject->subTypeTableExists(CatalogTypeEnum::BEETSREMOTE));
    }

    public function testSubTypeValueExistsChecksTheBackendsOwnTable(): void
    {
        // the beetsremote check used to name `catalog_beets` and a column it has no such column
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `catalog_beetsremote` WHERE `uri` = ?', ['http://host'])
            ->willReturn('1');

        static::assertTrue($this->subject->subTypeValueExists(CatalogTypeEnum::BEETSREMOTE, 'uri', 'http://host'));
    }

    public function testTryAcquireActionLockReflectsWhoWonTheLock(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with('SELECT GET_LOCK(?, 0)', ['ampache_sse_action_some-key'])
            ->willReturn('1', '0');

        static::assertTrue($this->subject->tryAcquireActionLock('some-key'));
        static::assertFalse($this->subject->tryAcquireActionLock('some-key'));
    }

    public function testTryAcquireProcessingLockReflectsWhoWonTheLock(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with('SELECT GET_LOCK(?, 0)', ['ampache_catalog_7'])
            ->willReturn('1', '0');

        static::assertTrue($this->subject->tryAcquireProcessingLock(7));
        static::assertFalse($this->subject->tryAcquireProcessingLock(7));
    }

    public function testUpdateSettingsWritesTheThreeFormFields(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `catalog` SET `name` = ?, `rename_pattern` = ?, `sort_pattern` = ? WHERE `id` = ?',
                ['renamed', '%T', '%a', 7]
            );

        $this->subject->updateSettings(7, 'renamed', '%T', '%a');
    }

    protected function setUp(): void
    {
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new CatalogRepository($this->connection, $this->configContainer);
    }
}
