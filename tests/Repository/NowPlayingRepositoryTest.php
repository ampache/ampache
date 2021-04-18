<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Doctrine\DBAL\Connection;
use Mockery\MockInterface;

class NowPlayingRepositoryTest extends MockeryTestCase
{
    /** @var MockInterface|Connection */
    private MockInterface $connection;

    private NowPlayingRepository $subject;

    public function setUp(): void
    {
        $this->connection = $this->mock(Connection::class);

        $this->subject = new NowPlayingRepository(
            $this->connection
        );
    }

    public function testCollectGarbageDeletes(): void
    {
        $sql = <<<SQL
        DELETE FROM `now_playing` USING `now_playing`
        LEFT JOIN `session` ON `session`.`id` = `now_playing`.`id`
        WHERE (
            `session`.`id` IS NULL AND
            `now_playing`.`id` NOT IN (SELECT `username` FROM `user`)
        ) OR `now_playing`.`expire` < ?
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                \Mockery::type('array')
            )
            ->once();

        $this->subject->collectGarbage();
    }

    public function testTruncateDeletes(): void
    {
        $this->connection->shouldReceive('executeQuery')
            ->with('TRUNCATE `now_playing`')
            ->once();

        $this->subject->truncate();
    }
}
