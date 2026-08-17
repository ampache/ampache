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

use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Repository\Model\User;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class SearchRepositoryTest extends TestCase
{
    private CatalogCounterInterface&MockObject $catalogCounter;
    private DatabaseConnectionInterface&MockObject $connection;
    private SearchRepository $subject;

    public function testCollectGarbageOnlyTouchesPrefixedKeys(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willReturnCallback(function (string $sql): PDOStatement {
                // it must leave the playlist rows for PlaylistRepository, and drop only orphans
                self::assertStringContainsString("LIKE 'smart\_%'", $sql);
                self::assertStringContainsString("NOT IN (SELECT CONCAT('smart_', `id`) FROM `search`)", $sql);

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage();
    }

    public function testDeleteCollaboratorsClearsTheMapByThePrefixedId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;', ['smart_666']);

        $this->subject->deleteCollaborators($this->smartlist(666));
    }

    public function testDeleteRefreshesTheCachedTotal(): void
    {
        $this->catalogCounter->expects(static::once())
            ->method('count')
            ->with(CountableTableEnum::SEARCH);

        $this->subject->delete($this->smartlist(666));
    }

    public function testDeleteRemovesTheSavedSearch(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `search` WHERE `id` = ?', [666]);

        $this->subject->delete($this->smartlist(666));
    }

    public function testInsertBindsTheRandomFlagAsAnIntAndReturnsTheId(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->username = 'admin';

        $smartlist                 = $this->smartlist(0);
        $smartlist->name           = 'some-name';
        $smartlist->type           = 'public';
        $smartlist->logic_operator = 'AND';
        $smartlist->random         = 2;
        $smartlist->limit          = 25;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::stringContains('INSERT INTO `search`'),
                self::callback(static fn(array $params): bool => $params[5] === 'and' && $params[6] === 1)
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        self::assertSame(666, $this->subject->insert($smartlist, $user, 1234));
    }

    public function testInsertReturnsNullWhenNoIdCameBack(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->username = 'admin';

        $this->connection->expects(static::once())->method('query');
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(0);

        self::assertNull($this->subject->insert($this->smartlist(0), $user, 1234));
    }

    public function testNameExistsReportsAMatch(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(self::stringContains('WHERE `name` = ? AND `user` = ? AND `type` = ?'), ['some-name', 1, 'public'])
            ->willReturn('5');

        self::assertTrue($this->subject->nameExists('some-name', 1, 'public'));
    }

    public function testNameExistsReportsNoMatch(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(false);

        self::assertFalse($this->subject->nameExists('some-name', 1, 'public'));
    }

    public function testPersistBindsRandomAsAnIntForTheTinyintColumn(): void
    {
        $smartlist         = $this->smartlist(666);
        $smartlist->random = 0;

        $this->connection->expects(static::once())
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params): PDOStatement {
                self::assertSame(0, $params[4]);

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->persist($smartlist);
    }

    public function testPersistWritesTheSearchOnlyColumnsToo(): void
    {
        $smartlist                 = $this->smartlist(666);
        $smartlist->name           = 'Recent';
        $smartlist->type           = 'public';
        $smartlist->user           = 4;
        $smartlist->username       = 'admin';
        $smartlist->random         = 1;
        $smartlist->limit          = 50;
        $smartlist->logic_operator = 'AND';
        $smartlist->rules          = [['title', 'contains', 'a', null]];

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `search` SET `name` = ?, `type` = ?, `user` = ?, `username` = ?, `random` = ?, `limit` = ?, `logic_operator` = ?, `rules` = ? WHERE `id` = ?',
                ['Recent', 'public', 4, 'admin', 1, 50, 'and', '[["title","contains","a",null]]', 666]
            );

        $this->subject->persist($smartlist);
    }

    public function testSetLastCountSkipsAnUnsavedItem(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->setLastCount($this->smartlist(0), 12);
    }

    public function testSetLastCountWritesToTheSearchTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `search` SET `last_count` = ? WHERE `id` = ?', [12, 666]);

        $this->subject->setLastCount($this->smartlist(666), 12);
    }

    public function testSetLastDurationWritesToTheSearchTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `search` SET `last_duration` = ? WHERE `id` = ?', [300, 666]);

        $this->subject->setLastDuration($this->smartlist(666), 300);
    }

    public function testSetLastUpdateWritesToTheSearchTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `search` SET `last_update` = ? WHERE `id` = ?', [1234, 666]);

        $this->subject->setLastUpdate($this->smartlist(666), 1234);
    }

    public function testUpdateCollaboratorsClearsTheColumnAndTheMapWhenTheListIsEmpty(): void
    {
        $matcher = static::exactly(2);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($matcher): PDOStatement {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertSame(
                        ['UPDATE `search` SET `collaborate` = ? WHERE `id` = ?', ['', 666]],
                        [$sql, $params]
                    ),
                    default => self::assertSame(
                        ['DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;', ['smart_666']],
                        [$sql, $params]
                    ),
                };

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateCollaborators($this->smartlist(666), []);
    }

    public function testUpdateCollaboratorsKeysTheMapByThePrefixedId(): void
    {
        // the shared map holds `smart_666` for a saved search, against a bare id for a playlist
        $matcher = static::exactly(3);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($matcher): PDOStatement {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertSame(
                        ['UPDATE `search` SET `collaborate` = ? WHERE `id` = ?', ['2', 666]],
                        [$sql, $params]
                    ),
                    2 => self::assertSame(['smart_666'], $params),
                    default => self::assertSame(['smart_666', 2], $params),
                };

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateCollaborators($this->smartlist(666), [2]);
    }

    protected function setUp(): void
    {
        // `new User()` reaches Catalog::get_catalogs() through the `global $dic` bridge
        $catalogRepository = $this->createMock(CatalogRepositoryInterface::class);
        $catalogRepository->method('getIds')->willReturn([]);

        $globalDic = $this->createMock(ContainerInterface::class);
        $globalDic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            CatalogRepositoryInterface::class => $catalogRepository,
            default => $this->createMock(LoggerInterface::class),
        });
        $GLOBALS['dic'] = $globalDic;

        $this->connection     = $this->createMock(DatabaseConnectionInterface::class);
        $this->catalogCounter = $this->createMock(CatalogCounterInterface::class);

        $this->subject = new SearchRepository($this->connection, $this->catalogCounter);
    }

    private function smartlist(int $searchId): Smartlist
    {
        $smartlist     = new Smartlist();
        $smartlist->id = $searchId;

        return $smartlist;
    }
}
