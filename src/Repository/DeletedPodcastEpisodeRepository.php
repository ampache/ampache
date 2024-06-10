<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Generator;
use PDO;

/**
 * Manages database access related to deleted podcast-episodes
 *
 * Tables: `deleted_podcast_episodes`
 */
final readonly class DeletedPodcastEpisodeRepository implements DeletedPodcastEpisodeRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection)
    {
    }

    /**
    * Returns all deleted podcast episodes
    *
    * @return Generator<array{
    *  id: int,
    *  addition_time: int,
    *  delete_time: int,
    *  title: string,
    *  file: string,
    *  catalog: int,
    *  total_count: int,
    *  total_skip: int,
    *  podcast: int
    * }>
    */
    public function findAll(): Generator
    {
        $result = $this->connection->query('SELECT * FROM `deleted_podcast_episode`');

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            yield [
                'id' => (int) $row['id'],
                'addition_time' => (int) $row['addition_time'],
                'delete_time' => (int) $row['delete_time'],
                'title' => (string) $row['title'],
                'file' => (string) $row['file'],
                'catalog' => (int) $row['catalog'],
                'total_count' => (int) $row['total_count'],
                'total_skip' => (int) $row['total_skip'],
                'podcast' => (int) $row['podcast'],
            ];
        }
    }
}
