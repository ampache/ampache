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
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class UserRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private UserRepository $subject;

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

        static::assertSame(
            666,
            $this->subject->create(['username' => 'some-user', 'disabled' => 0, 'access' => 25])
        );
    }

    public function testCreateReturnsZeroWhenTheInsertFailed(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertSame(0, $this->subject->create(['username' => 'some-user']));
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

        static::assertSame('10.0.0.1', $this->subject->findActiveSessionIp('some-user', 123456, true));
    }

    public function testFindActiveSessionIpReturnsNullWhenNotLoggedIn(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        static::assertNull($this->subject->findActiveSessionIp('some-user', 123456, false));
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

        static::assertNull($this->subject->findByApiKey('some-api-key'));
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

        static::assertNull($this->subject->getValidationByUsername('some-user'));
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

        static::assertTrue($this->subject->hasOtherAdmin(666, true));
    }

    public function testHasOtherAdminReturnsFalseWhenThisIsTheLastOne(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        static::assertFalse($this->subject->hasOtherAdmin(666, false));
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

        static::assertSame(0, $this->subject->idByUsername('some-user'));
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

        static::assertSame('', $this->subject->retrievePasswordFromUser(666));
    }

    public function testSetFieldClearsATokenWithNull(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `user` SET `apikey` = ? WHERE `id` = ?', [null, 666]);

        static::assertTrue($this->subject->setField(666, UserFieldEnum::APIKEY, null));
    }

    public function testSetFieldWritesTheColumnFromTheEnum(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `user` SET `email` = ? WHERE `id` = ?', ['some@example.org', 666]);

        static::assertTrue($this->subject->setField(666, UserFieldEnum::EMAIL, 'some@example.org'));
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

        static::assertTrue($this->subject->setValidation(666, 'some-key'));
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new UserRepository(
            $this->connection
        );
    }
}
