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
use Ampache\Repository\Model\User;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WantedRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private WantedRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new WantedRepository(
            $this->connection
        );
    }

    public function testFindAllReturnDataWithoutUserRestriction(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $wantedId = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `wanted`',
                []
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $wantedId, false);

        static::assertSame(
            [$wantedId],
            $this->subject->findAll()
        );
    }

    public function testFindAllReturnDataWithUserRestriction(): void
    {
        $result = $this->createMock(PDOStatement::class);
        $user   = $this->createMock(User::class);

        $wantedId = 666;
        $userId   = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `user` = ?',
                [$userId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $wantedId, false);

        static::assertSame(
            [$wantedId],
            $this->subject->findAll($user)
        );
    }

    public function testFindReturnsNullIfItemWasNotFound(): void
    {
        $musicBrainzId = 'some-id';
        $userId        = 666;

        $user = $this->createMock(User::class);

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ? LIMIT 1',
                [$musicBrainzId, $userId]
            )
            ->willReturn(false);

        static::assertNull(
            $this->subject->find($musicBrainzId, $user)
        );
    }

    public function testFindReturnsWantedId(): void
    {
        $musicBrainzId = 'some-id';
        $userId        = 666;
        $wantedId      = 123;

        $user = $this->createMock(User::class);

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ? LIMIT 1',
                [$musicBrainzId, $userId]
            )
            ->willReturn((string) $wantedId);

        static::assertSame(
            $wantedId,
            $this->subject->find($musicBrainzId, $user)
        );
    }

    public function testDeleteByMusicbrainzIdDeletesWithoutUser(): void
    {
        $musicBrainzId = 'some-mbid';

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `wanted` WHERE `mbid` = ?',
                [$musicBrainzId]
            );

        $this->subject->deleteByMusicbrainzId($musicBrainzId);
    }

    public function testDeleteByMusicbrainzIdDeletesUser(): void
    {
        $musicBrainzId = 'some-mbid';
        $userId        = 666;

        $user = $this->createMock(User::class);

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `wanted` WHERE `mbid` = ? AND `user` = ?',
                [$musicBrainzId, $userId]
            );

        $this->subject->deleteByMusicbrainzId($musicBrainzId, $user);
    }

    public function testGetAcceptedCountReturnsValue(): void
    {
        $value = 1234;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(`id`) AS `wanted_cnt` FROM `wanted` WHERE `accepted` = 1')
            ->willReturn((string) $value);

        static::assertSame(
            $value,
            $this->subject->getAcceptedCount()
        );
    }
}
