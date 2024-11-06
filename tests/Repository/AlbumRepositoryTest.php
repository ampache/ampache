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
use Ampache\Repository\Model\Album;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class AlbumRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;

    private AlbumRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new AlbumRepository(
            $this->connection
        );
    }

    public function testDeleteDeletes(): void
    {
        $album = $this->createMock(Album::class);

        $albumId = 666;

        $album->expects(static::once())
            ->method('getId')
            ->willReturn($albumId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `album` WHERE `id` = ?',
                [$albumId]
            );

        $this->subject->delete($album);
    }

    public function testCollectGarbageDeletes(): void
    {
        $this->connection->expects(static::exactly(7))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['DELETE FROM `album_map` WHERE `object_type` = \'album\' AND `album_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL)'],
                    ['DELETE FROM `album_map` WHERE `object_id` NOT IN (SELECT `id` FROM `artist`)'],
                    ['DELETE FROM `album_map` WHERE `album_map`.`album_id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`)'],
                    ['DELETE FROM `album_map` WHERE `album_map`.`album_id` IN (SELECT `album_id` FROM (SELECT DISTINCT `album_map`.`album_id` FROM `album_map` LEFT JOIN `artist_map` ON `artist_map`.`object_type` = `album_map`.`object_type` AND `artist_map`.`artist_id` = `album_map`.`object_id` AND `artist_map`.`object_id` = `album_map`.`album_id` WHERE `artist_map`.`artist_id` IS NULL AND `album_map`.`object_type` = \'album\') AS `null_album`)'],
                    ['DELETE FROM `album` WHERE `album`.`id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`) AND `album`.`id` NOT IN (SELECT DISTINCT `album_id` FROM `album_map`)'],
                    ['DELETE FROM `album_disk` WHERE `album_id` NOT IN (SELECT `id` FROM `album`)'],
                    ['SELECT `id` FROM `album_disk` WHERE CONCAT(`album_id`, \'_\', `disk`) NOT IN (SELECT CONCAT(`album`, \'_\', `disk`) AS `id` FROM `song`);'],
                )
            );

        $this->subject->collectGarbage();
    }

    public function testGetByName(): void
    {
        $albumId  = 666;
        $name     = 'some-name';
        $artistId = 1234;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `album`.`id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, \'\'), \' \', `album`.`name`)) = ?) AND `album`.`album_artist` = ?',
                [$name, $name, $artistId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $albumId, false);

        static::assertSame(
            [$albumId],
            $this->subject->getByName($name, $artistId)
        );
    }

    public function testGetByMbidGroupReturnsData(): void
    {
        $musicBrainzId = '1234';
        $albumId       = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `album`.`id` FROM `album` WHERE `album`.`mbid_group` = ?',
                [$musicBrainzId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $albumId, false);

        static::assertSame(
            [$albumId],
            $this->subject->getByMbidGroup($musicBrainzId)
        );
    }

    public function testGetArtistMapReturnsArtistList(): void
    {
        $album  = $this->createMock(Album::class);
        $result = $this->createMock(PDOStatement::class);

        $objectType = 'some-object';
        $albumId    = 666;
        $artistId   = 42;

        $album->expects(static::once())
            ->method('getId')
            ->willReturn($albumId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `object_id` FROM `album_map` WHERE `object_type` = ? AND `album_id` = ?',
                [$objectType, $albumId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $artistId, false);

        static::assertSame(
            [$artistId],
            $this->subject->getArtistMap($album, $objectType)
        );
    }

    public function testGetAlbumArtistIdReturnsNullIfNotFound(): void
    {
        $albumId = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT DISTINCT `album_artist` FROM `album` WHERE `id` = ?;',
                [$albumId]
            )
            ->willReturn(false);

        static::assertNull(
            $this->subject->getAlbumArtistId($albumId)
        );
    }

    public function testGetAlbumArtistIdReturnsAlbumArtistId(): void
    {
        $albumId = 666;
        $result  = 42;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT DISTINCT `album_artist` FROM `album` WHERE `id` = ?;',
                [$albumId]
            )
            ->willReturn((string) $result);

        static::assertSame(
            $result,
            $this->subject->getAlbumArtistId($albumId)
        );
    }

    public function testGetNamesReturnsArrayWithDefaultsIfEmpty(): void
    {
        $albumId = 666;

        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with(
                'SELECT `album`.`prefix`, `album`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, \'\'), \' \', `album`.`name`)) AS `name` FROM `album` WHERE `id` = ?',
                [$albumId]
            )
            ->willReturn(false);

        static::assertSame(
            [
                'prefix' => '',
                'basename' => '',
                'name' => ''
            ],
            $this->subject->getNames($albumId)
        );
    }

    public function testGetNamesReturnsRow(): void
    {
        $albumId = 666;
        $data    = ['some-data'];

        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with(
                'SELECT `album`.`prefix`, `album`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, \'\'), \' \', `album`.`name`)) AS `name` FROM `album` WHERE `id` = ?',
                [$albumId]
            )
            ->willReturn($data);

        static::assertSame(
            $data,
            $this->subject->getNames($albumId)
        );
    }
}
