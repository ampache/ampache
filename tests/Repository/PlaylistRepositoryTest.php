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
use Ampache\Repository\Model\Playlist;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaylistRepositoryTest extends TestCase
{
    private CatalogCounterInterface&MockObject $catalogCounter;
    private DatabaseConnectionInterface&MockObject $connection;
    private PlaylistRepository $subject;

    public function testAddTracksBindsOnePlaceholderGroupPerRow(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'REPLACE INTO `playlist_data` (`playlist`, `object_id`, `object_type`, `track`) VALUES (?, ?, ?, ?), (?, ?, ?, ?)',
                [666, 21, 'song', 1, 666, 33, 'video', 2]
            );

        $this->subject->addTracks($this->playlist(666), [[21, 'song', 1], [33, 'video', 2]]);
    }

    public function testAddTracksDoesNothingForAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->addTracks($this->playlist(666), []);
    }

    public function testCollectGarbageClearsDeadEntriesThenOrphanedCollaborators(): void
    {
        $statements = [];

        $this->connection->method('query')
            ->willReturnCallback(function (string $sql) use (&$statements): PDOStatement {
                $statements[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage();

        $map = array_values(array_filter($statements, static fn(string $sql): bool => str_contains($sql, 'user_playlist_map')));

        // the smartlist rows are SearchRepository's to clean, so this sweep has to skip them
        self::assertCount(1, $map);
        self::assertStringContainsString("NOT LIKE 'smart\_%'", $map[0]);
        self::assertStringContainsString('NOT IN (SELECT `id` FROM `playlist`)', $map[0]);
        self::assertNotEmpty(array_filter($statements, static fn(string $sql): bool => str_contains($sql, 'playlist_data')));
    }

    public function testDeleteAllTracksEmptiesTheList(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ?', [666]);

        $this->subject->deleteAllTracks($this->playlist(666));
    }

    public function testDeleteCollaboratorsClearsTheMapByThePlainId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;', [666]);

        $this->subject->deleteCollaborators($this->playlist(666));
    }

    public function testDeleteRefreshesTheCachedTotal(): void
    {
        $this->catalogCounter->expects(static::once())
            ->method('count')
            ->with(CountableTableEnum::PLAYLIST);

        $this->subject->delete($this->playlist(666));
    }

    public function testDeleteRemovesTheListItsEntriesAndItsStats(): void
    {
        // playlist_data, playlist, then the three object_count tables
        $this->connection->expects(static::exactly(5))
            ->method('query')
            ->with(self::anything(), [666]);

        $this->subject->delete($this->playlist(666));
    }

    public function testDeleteTrackByNumberLimitsToOneRow(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('`playlist_data`.`track` = ? LIMIT 1'), [666, 3]);

        $this->subject->deleteTrackByNumber($this->playlist(666), 3);
    }

    public function testGetIdsByCatalogRepeatsTheCatalogForEveryMediaType(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::stringContains('WHERE `song`.`catalog` = ? OR `live_stream`.`catalog` = ? OR `podcast_episode`.`catalog` = ? OR `video`.`catalog` = ?;'),
                [7, 7, 7, 7]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('666', false);

        self::assertSame([666], $this->subject->getIdsByCatalog(7));
    }

    public function testGetItemsOfTypeReadsTheDurationColumnForATimedType(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('`song`.`time` FROM `playlist_data`'))
            ->willReturn($this->createMock(PDOStatement::class));

        $this->subject->getItemsOfType(666, 'song', 42, false, true, false);
    }

    public function testGetItemsOfTypeSubstitutesZeroForATypeWithNoDurationColumn(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::logicalAnd(
                self::stringContains('0 AS `time` FROM `playlist_data`'),
                self::logicalNot(self::stringContains('`live_stream`.`time`'))
            ))
            ->willReturn($this->createMock(PDOStatement::class));

        $this->subject->getItemsOfType(666, 'live_stream', 42, false, true, false);
    }

    public function testGetLastTrackNumberReadsTheMaximum(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT MAX(`track`) AS `track` FROM `playlist_data` WHERE `playlist` = ?', [666])
            ->willReturn('4');

        self::assertSame(4, $this->subject->getLastTrackNumber($this->playlist(666)));
    }

    public function testPersistWritesTheSharedColumnsInOneStatement(): void
    {
        $playlist           = $this->playlist(666);
        $playlist->name     = 'Mixed Test';
        $playlist->type     = 'private';
        $playlist->user     = 4;
        $playlist->username = 'admin';

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `playlist` SET `name` = ?, `type` = ?, `user` = ?, `username` = ? WHERE `id` = ?',
                ['Mixed Test', 'private', 4, 'admin', 666]
            );

        $this->subject->persist($playlist);
    }

    public function testReplaceTrackAtNumberClearsThePositionFirst(): void
    {
        $matcher = static::exactly(2);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql) use ($matcher): PDOStatement {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertStringContainsString('DELETE FROM `playlist_data`', $sql);
                } else {
                    self::assertStringContainsString('INSERT INTO `playlist_data`', $sql);
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->replaceTrackAtNumber($this->playlist(666), 21, 3);
    }

    public function testSetLastCountSkipsANegativeTotal(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->setLastCount($this->playlist(666), -1);
    }

    public function testSetLastCountSkipsAnUnsavedItem(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->setLastCount($this->playlist(0), 12);
    }

    public function testSetLastCountWritesToThePlaylistTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `playlist` SET `last_count` = ? WHERE `id` = ?', [12, 666]);

        $this->subject->setLastCount($this->playlist(666), 12);
    }

    public function testSetLastDurationWritesToThePlaylistTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `playlist` SET `last_duration` = ? WHERE `id` = ?', [300, 666]);

        $this->subject->setLastDuration($this->playlist(666), 300);
    }

    public function testSetLastUpdateWritesToThePlaylistTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `playlist` SET `last_update` = ? WHERE `id` = ?', [1234, 666]);

        $this->subject->setLastUpdate($this->playlist(666), 1234);
    }

    public function testSetTrackNumbersDoesNothingForAnEmptySet(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->setTrackNumbers([]);
    }

    public function testSetTrackNumbersWritesOneUpsertForTheWholeSet(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `playlist_data` (`id`, `track`) VALUES (?, ?), (?, ?) ON DUPLICATE KEY UPDATE `track`=VALUES(`track`)',
                [7, 1, 9, 2]
            );

        $this->subject->setTrackNumbers([7 => 1, 9 => 2]);
    }

    public function testUpdateCollaboratorsClearsTheColumnAndTheMapWhenTheListIsEmpty(): void
    {
        $matcher = static::exactly(2);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($matcher): PDOStatement {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertSame(
                        ['UPDATE `playlist` SET `collaborate` = ? WHERE `id` = ?', ['', 666]],
                        [$sql, $params]
                    ),
                    default => self::assertSame(
                        ['DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;', [666]],
                        [$sql, $params]
                    ),
                };

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateCollaborators($this->playlist(666), []);
    }

    public function testUpdateCollaboratorsKeysTheMapByThePlainId(): void
    {
        // the column, a delete that spares the users being kept, then one insert per user
        $matcher = static::exactly(4);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($matcher): PDOStatement {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertSame(
                        ['UPDATE `playlist` SET `collaborate` = ? WHERE `id` = ?', ['2,3', 666]],
                        [$sql, $params]
                    ),
                    2 => self::assertSame(
                        ['DELETE FROM `user_playlist_map` WHERE `playlist_id` = ? AND `user_id` NOT IN (2,3);', [666]],
                        [$sql, $params]
                    ),
                    3 => self::assertSame(
                        ['INSERT IGNORE INTO `user_playlist_map` (`playlist_id`, `user_id`) VALUES (?, ?);', [666, 2]],
                        [$sql, $params]
                    ),
                    default => self::assertSame([666, 3], $params),
                };

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateCollaborators($this->playlist(666), [2, 3]);
    }

    protected function setUp(): void
    {
        $this->connection     = $this->createMock(DatabaseConnectionInterface::class);
        $this->catalogCounter = $this->createMock(CatalogCounterInterface::class);

        $this->subject = new PlaylistRepository($this->connection, $this->catalogCounter);
    }

    private function playlist(int $playlistId): Playlist
    {
        $playlist     = new Playlist();
        $playlist->id = $playlistId;

        return $playlist;
    }
}
