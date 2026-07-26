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
use Ampache\Repository\Model\Playlist;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaylistRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private PlaylistRepository $subject;

    public function testCollectGarbageOnlyTouchesUnprefixedKeys(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willReturnCallback(function (string $sql): PDOStatement {
                // it must leave the smartlist rows for SearchRepository, and drop only orphans
                static::assertStringContainsString("NOT LIKE 'smart\_%'", $sql);
                static::assertStringContainsString('NOT IN (SELECT `id` FROM `playlist`)', $sql);

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage();
    }

    public function testDeleteCollaboratorsClearsTheMapByThePlainId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;', [666]);

        $this->subject->deleteCollaborators($this->playlist(666));
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

    public function testUpdateCollaboratorsClearsTheMapWhenTheListIsEmpty(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;', [666]);

        $this->subject->updateCollaborators($this->playlist(666), []);
    }

    public function testUpdateCollaboratorsKeysTheMapByThePlainId(): void
    {
        // one delete that spares the users being kept, then one insert per user
        $matcher = static::exactly(3);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($matcher): PDOStatement {
                match ($matcher->numberOfInvocations()) {
                    1 => static::assertSame(
                        ['DELETE FROM `user_playlist_map` WHERE `playlist_id` = ? AND `user_id` NOT IN (2,3);', [666]],
                        [$sql, $params]
                    ),
                    2 => static::assertSame(
                        ['INSERT IGNORE INTO `user_playlist_map` (`playlist_id`, `user_id`) VALUES (?, ?);', [666, 2]],
                        [$sql, $params]
                    ),
                    default => static::assertSame([666, 3], $params),
                };

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateCollaborators($this->playlist(666), [2, 3]);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new PlaylistRepository($this->connection);
    }

    private function playlist(int $playlistId): Playlist
    {
        $playlist     = new Playlist();
        $playlist->id = $playlistId;

        return $playlist;
    }
}
