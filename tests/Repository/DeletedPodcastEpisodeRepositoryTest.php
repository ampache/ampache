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
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeletedPodcastEpisodeRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private DeletedPodcastEpisodeRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new DeletedPodcastEpisodeRepository(
            $this->connection,
        );
    }

    public function testFindAllReturnsData(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $id           = 666;
        $additionTime = 123;
        $deleteTime   = 456;
        $title        = 'some-title';
        $file         = 'some-file';
        $catalog      = 789;
        $totalCount   = 111;
        $totalSkip    = 222;
        $podcast      = 333;

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT * FROM `deleted_podcast_episode`')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'id' => (string) $id,
                    'addition_time' => (string) $additionTime,
                    'delete_time' => (string) $deleteTime,
                    'title' => $title,
                    'file' => $file,
                    'catalog' => (string) $catalog,
                    'total_count' => (string) $totalCount,
                    'total_skip' => (string) $totalSkip,
                    'podcast' => (string) $podcast,
                ],
                false
            );

        static::assertSame(
            [[
                'id' => $id,
                'addition_time' => $additionTime,
                'delete_time' => $deleteTime,
                'title' => $title,
                'file' => $file,
                'catalog' => $catalog,
                'total_count' => $totalCount,
                'total_skip' => $totalSkip,
                'podcast' => $podcast,
            ]],
            iterator_to_array($this->subject->findAll())
        );
    }
}
