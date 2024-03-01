<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Artist;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class ArtistRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;

    private ArtistRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new ArtistRepository(
            $this->connection
        );
    }

    public function testDeleteDeletes(): void
    {
        $artistId = 666;

        $artist = $this->createMock(Artist::class);

        $artist->expects(static::once())
            ->method('getId')
            ->willReturn($artistId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `artist` WHERE `id` = ?',
                [$artistId]
            );

        $this->subject->delete($artist);
    }

    public function testCollectGarbageCleansUp(): void
    {
        $this->connection->expects(static::exactly(5))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['DELETE FROM `artist_map` WHERE `object_type` = \'album\' AND `object_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL);'],
                    ['DELETE FROM `artist_map` WHERE `object_type` = \'album\' AND `object_id` NOT IN (SELECT `id` FROM `album`);'],
                    ['DELETE FROM `artist_map` WHERE `object_type` = \'song\' AND `object_id` NOT IN (SELECT `id` FROM `song`);'],
                    ['DELETE FROM `artist_map` WHERE `artist_id` NOT IN (SELECT `id` FROM `artist`);'],
                    ['DELETE FROM `artist` WHERE `id` IN (SELECT `id` FROM (SELECT `id` FROM `artist` LEFT JOIN (SELECT DISTINCT `song`.`artist` AS `artist_id` FROM `song` UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id` FROM `album` UNION SELECT DISTINCT `wanted`.`artist` AS `artist_id` FROM `wanted` UNION SELECT DISTINCT `clip`.`artist` AS `artist_id` FROM `clip` UNION SELECT DISTINCT `artist_id` FROM `artist_map`) AS `artist_map` ON `artist_map`.`artist_id` = `artist`.`id` WHERE `artist_map`.`artist_id` IS NULL) AS `null_artist`);'],
                )
            );

        $this->subject->collectGarbage();
    }

    public function testFindByNameReturnsNullIfNoEntryWasFound(): void
    {
        $value = 'snafu';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, \'\'), \' \', `artist`.`name`)) = ? ',
                [$value, $value]
            )
            ->willReturn(false);

        static::assertNull(
            $this->subject->findByName($value)
        );
    }
}
