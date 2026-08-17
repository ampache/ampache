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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\UserFieldEnum;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class UserRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private UserRepository $subject;

    public function testCountByCatalogFilterGroupCountsTheAssignedUsers(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(1) AS `count` FROM `user` WHERE `catalog_filter_group` = ?', [4])
            ->willReturn('3');

        self::assertSame(3, $this->subject->countByCatalogFilterGroup(4));
    }

    public function testCreateLeavesOmittedColumnsOutOfTheStatement(): void
    {
        // an absent optional column has to stay out of the INSERT so the schema default applies to it
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `user` (`username`, `disabled`, `access`) VALUES(?, ?, ?)',
                ['some-user', 0, 25]
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        self::assertSame(
            666,
            $this->subject->create(['username' => 'some-user', 'disabled' => 0, 'access' => 25])
        );
    }

    public function testCreateReturnsZeroWhenTheInsertFailed(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        self::assertSame(0, $this->subject->create(['username' => 'some-user']));
    }

    public function testDeleteAlsoDropsAccessRulesAndSessions(): void
    {
        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['DELETE FROM `user` WHERE `id` = ?', [666]],
                    ['DELETE FROM `access_list` WHERE `user` = ?', [666]],
                    ['DELETE FROM `session` WHERE `username` = ?', ['some-user']],
                )
            );

        $this->subject->delete(666, 'some-user');
    }

    public function testFindActiveSessionIpMatchesAPerpetualApiSessionOnItsType(): void
    {
        // a perpetual api session has expire = 0, so an expiry comparison alone would never find it
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                "SELECT `ip` FROM `session` WHERE `username` = ? AND ((`expire` = 0 AND `type` = 'api') OR `expire` > ?);",
                ['some-user', 123456]
            )
            ->willReturn('10.0.0.1');

        self::assertSame('10.0.0.1', $this->subject->findActiveSessionIp('some-user', 123456, true));
    }

    public function testFindActiveSessionIpReturnsNullWhenNotLoggedIn(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        self::assertNull($this->subject->findActiveSessionIp('some-user', 123456, false));
    }

    public function testFindByApiKeyFallsBackToTheSessionAndThenTheHashedKeys(): void
    {
        $result = $this->createMock(PDOStatement::class);

        // no plain key, no api session, and no user whose hashed key matches
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `apikey`, `username` FROM `user`')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => '1', 'apikey' => 'some-key', 'username' => 'some-user'], false);

        self::assertNull($this->subject->findByApiKey('some-api-key'));
    }

    public function testGetRowsByIdsCastsTheIdsIntoTheStatement(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('WHERE `id` IN (1,0,3)'))
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => '1', 'username' => 'foo'], false);

        self::assertSame(
            [['id' => '1', 'username' => 'foo']],
            $this->subject->getRowsByIds([1, 'x', 3])
        );
    }

    public function testGetRowsByIdsReturnsNothingForAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        self::assertSame([], $this->subject->getRowsByIds([]));
    }

    public function testGetValidationByUsernameReturnsNullForAClearedValidation(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `validation` FROM `user` WHERE `username` = ?',
                ['some-user']
            )
            ->willReturn(null);

        self::assertNull($this->subject->getValidationByUsername('some-user'));
    }

    public function testHasOtherAdminIgnoresDisabledAccountsWhenAsked(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                "SELECT `id` FROM `user` WHERE `disabled` = '0' AND `access` = ? AND `id` != ? ",
                [AccessLevelEnum::ADMIN->value, 666]
            )
            ->willReturn('42');

        self::assertTrue($this->subject->hasOtherAdmin(666, true));
    }

    public function testHasOtherAdminReturnsFalseWhenThisIsTheLastOne(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        self::assertFalse($this->subject->hasOtherAdmin(666, false));
    }

    public function testIdByUsernameReturnsZeroForAnUnknownName(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `username` = ?',
                ['some-user']
            )
            ->willReturn(false);

        self::assertSame(0, $this->subject->idByUsername('some-user'));
    }

    public function testResetCatalogFilterGroupPutsThemBackOnDefault(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `user` SET `catalog_filter_group` = 0 WHERE `catalog_filter_group` = ?', [4]);

        $this->subject->resetCatalogFilterGroup(4);
    }

    public function testResetMissingCatalogFilterGroupsSweepsEveryDanglingUser(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `user` SET `catalog_filter_group` = 0 WHERE `catalog_filter_group` NOT IN (SELECT `id` FROM `catalog_filter_group`);');

        $this->subject->resetMissingCatalogFilterGroups();
    }

    public function testRetrievePasswordFromUserReturnsAnEmptyStringForAnUnknownUser(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `password` FROM `user` WHERE `id` = ?',
                [666]
            )
            ->willReturn(false);

        self::assertSame('', $this->subject->retrievePasswordFromUser(666));
    }

    public function testSetFieldClearsATokenWithNull(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `user` SET `apikey` = ? WHERE `id` = ?', [null, 666]);

        self::assertTrue($this->subject->setField(666, UserFieldEnum::APIKEY, null));
    }

    public function testSetFieldWritesTheColumnFromTheEnum(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `user` SET `email` = ? WHERE `id` = ?', ['some@example.org', 666]);

        self::assertTrue($this->subject->setField(666, UserFieldEnum::EMAIL, 'some@example.org'));
    }

    public function testSetUserDataReplacesTheStoredCounter(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('REPLACE INTO `user_data` SET `user` = ?, `key` = ?, `value` = ?;', [666, 'song', 42]);

        $this->subject->setUserData(666, 'song', 42);
    }

    public function testSetValidationAlsoDisablesTheAccount(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with("UPDATE `user` SET `validation` = ?, `disabled`='1' WHERE `id` = ?", ['some-key', 666]);

        self::assertTrue($this->subject->setValidation(666, 'some-key'));
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new UserRepository(
            $this->connection,
            $this->logger,
        );
    }
}
