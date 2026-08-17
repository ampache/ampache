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
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Wanted;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WantedRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private WantedRepository $subject;

    public function testCollectGarbageContinuesAfterAFailedQuery(): void
    {
        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        $this->logger->expects(static::exactly(3))
            ->method('debug');

        $this->subject->collectGarbage();
    }

    public function testCollectGarbagePerformsCleanup(): void
    {
        $queries = [];

        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$queries): PDOStatement {
                $queries[] = $query;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage();

        static::assertSame(
            'DELETE FROM `wanted` WHERE `wanted`.`artist` NOT IN (SELECT `artist`.`id` FROM `artist`)',
            $queries[0]
        );
        static::assertSame(
            'DELETE FROM `wanted` WHERE `wanted`.`mbid` IS NOT NULL AND EXISTS (SELECT 1 FROM `album` WHERE `album`.`mbid_group` = `wanted`.`mbid`)',
            $queries[1]
        );
        static::assertSame(
            "DELETE FROM `wanted` WHERE `wanted`.`artist` IS NOT NULL AND `wanted`.`name` IS NOT NULL AND EXISTS (SELECT 1 FROM `album` WHERE `album`.`album_artist` = `wanted`.`artist` AND (`album`.`name` = `wanted`.`name` OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = `wanted`.`name`))",
            $queries[2]
        );
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

        self::assertSame(
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

        self::assertSame(
            [$wantedId],
            $this->subject->findAll($user)
        );
    }

    public function testFindByMusicBrainzIdReturnsNullIfNoEntryWasFound(): void
    {
        $mbid = 'some-mbid';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `mbid` = ?',
                [$mbid]
            )
            ->willReturn(false);

        self::assertNull(
            $this->subject->findByMusicBrainzId($mbid)
        );
    }

    public function testFindByNameReturnsNullIfNoEntryWasFound(): void
    {
        $name = 'some-value';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `name` = ? LIMIT 1',
                [$name]
            )
            ->willReturn(false);

        self::assertNull(
            $this->subject->findByName($name)
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

        self::assertNull(
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

        self::assertSame(
            $wantedId,
            $this->subject->find($musicBrainzId, $user)
        );
    }

    public function testGetAcceptedCountReturnsValue(): void
    {
        $value = 1234;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(`id`) AS `wanted_cnt` FROM `wanted` WHERE `accepted` = 1')
            ->willReturn((string) $value);

        self::assertSame(
            $value,
            $this->subject->getAcceptedCount()
        );
    }

    public function testGetRowsByIdsCastsTheIdsIntoTheStatement(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT * FROM `wanted` WHERE `id` IN (1,0,3)')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => 1], false);

        static::assertSame([['id' => 1]], $this->subject->getRowsByIds([1, 'x', 3]));
    }

    public function testGetRowsByIdsReturnsNothingForAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        static::assertSame([], $this->subject->getRowsByIds([]));
    }

    public function testMigrateArtistMigrates(): void
    {
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `wanted` SET `artist` = ? WHERE `artist` = ?',
                [
                    $newObjectId,
                    $oldObjectId
                ]
            );

        $this->subject->migrateArtist($oldObjectId, $newObjectId);
    }

    public function testPrototypeReturnsNewInstance(): void
    {
        self::assertInstanceOf(
            Wanted::class,
            $this->subject->prototype()
        );
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new WantedRepository(
            $this->connection,
            $this->logger,
        );
    }
}
