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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Catalog\CatalogMapTableEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CatalogMapRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private CatalogMapRepository $subject;

    public function testAddBindsEveryValue(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) VALUES (?, ?, ?);',
                [7, 'podcast', 666]
            );

        $this->subject->add(7, 'podcast', 666);
    }

    public function testAddForArtistBindsTheArtistOncePerUnionBranch(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                static::stringContains('INSERT IGNORE INTO `catalog_map`'),
                [42, 42, 42, 42]
            );

        $this->subject->addForArtist(42);
    }

    public function testCollectGarbageAlwaysSweepsCatalogZeroEvenWithNoTables(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `catalog_map` WHERE `catalog_id` = 0');

        $this->subject->collectGarbage([]);
    }

    public function testCollectGarbageCarriesOnAfterAFailedStatement(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;
                if (count($calls) === 1) {
                    throw new QueryFailedException('nope');
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->logger->expects(static::once())
            ->method('debug');

        $this->subject->collectGarbage([CatalogMapTableEnum::VIDEO]);

        static::assertCount(2, $calls);
        static::assertSame('DELETE FROM `catalog_map` WHERE `catalog_id` = 0', $calls[1]);
    }

    public function testCollectGarbageSweepsTheThreeArtistRolesBeforeCatalogZero(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage([CatalogMapTableEnum::ARTIST]);

        static::assertStringContainsString("`object_type` = 'album_artist'", $calls[0]);
        static::assertStringContainsString("`object_type` = 'song_artist'", $calls[1]);
        static::assertStringContainsString("`object_type` = 'artist'", $calls[2]);
        static::assertSame('DELETE FROM `catalog_map` WHERE `catalog_id` = 0', $calls[3]);
    }

    public function testCollectGarbageUsesTheTableNameOfANonArtistType(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage([CatalogMapTableEnum::PODCAST_EPISODE]);

        static::assertSame(
            'DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `podcast_episode`.`catalog` AS `catalog_id`, `podcast_episode`.`id` AS `object_id` FROM `podcast_episode`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` WHERE `catalog_map`.`object_type` = \'podcast_episode\' AND `valid_maps`.`object_id` IS NULL;',
            $calls[0]
        );
    }

    public function testDeleteForObjectRemovesEveryMappingOfIt(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `catalog_map` WHERE `object_id` = ? AND `object_type` = ?',
                [666, 'album']
            );

        $this->subject->deleteForObject('album', 666);
    }

    public function testMigrateKeepsWhatTheTargetAlreadyHad(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [42, 'artist', 666]
            );

        static::assertTrue($this->subject->migrate('artist', 666, 42));
    }

    public function testMigrateReportsAFailedUpdate(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('nope'));

        static::assertFalse($this->subject->migrate('artist', 666, 42));
    }

    public function testRebuildDerivesThePlaylistCatalogFromItsSongs(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(static::stringContains("SELECT `song`.`catalog`, 'playlist', `playlist`.`id` FROM `playlist`"));

        $this->subject->rebuild(CatalogMapTableEnum::PLAYLIST);
    }

    public function testRebuildTakesTheCatalogOffTheTableItself(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `album_disk`.`catalog`, \'album_disk\', `album_disk`.`id` FROM `album_disk` GROUP BY `album_disk`.`catalog`, \'album_disk\', `album_disk`.`id`;');

        $this->subject->rebuild(CatalogMapTableEnum::ALBUM_DISK);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new CatalogMapRepository(
            $this->connection,
            $this->logger
        );
    }
}
