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

use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\TagCountTypeEnum;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class TagRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private CatalogCounterInterface&MockObject $catalogCounter;
    private DatabaseConnectionInterface&MockObject $connection;
    private TagRepository $subject;

    public function testAddMapReturnsTheNewMapId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT IGNORE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) VALUES (?, ?, ?, ?)',
                [666, 0, 'song', 42]
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(7);

        static::assertSame(7, $this->subject->addMap(666, 'song', 42, 0));
    }

    public function testCollectGarbageRecountsEveryTypeAfterTheDeletes(): void
    {
        // seven deletes, then a recount and a zeroing statement for each of the four counted types
        $this->connection->expects(static::exactly(15))->method('query');

        $this->subject->collectGarbage();
    }

    public function testCreateTakesTheInsertIdBeforeTheCounterRunsItsOwnQueries(): void
    {
        $calls = [];

        $this->connection->expects(static::once())
            ->method('query')
            ->with('REPLACE INTO `tag` SET `name` = ?', ['some-genre'])
            ->willReturnCallback(function () use (&$calls): PDOStatement {
                $calls[] = 'insert';

                return $this->createMock(PDOStatement::class);
            });

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturnCallback(function () use (&$calls): int {
                $calls[] = 'id';

                return 42;
            });

        $this->catalogCounter->expects(static::once())
            ->method('count')
            ->willReturnCallback(function () use (&$calls): int {
                $calls[] = 'count';

                return 1;
            });

        static::assertSame(42, $this->subject->create('some-genre'));
        static::assertSame(['insert', 'id', 'count'], $calls);
    }

    public function testDecrementCountRefusesToGoNegative(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `tag` SET `song` = `song` - 1 WHERE `id` = ? AND `song` > 0;', [666]);

        $this->subject->decrementCount(666, TagCountTypeEnum::SONG);
    }

    public function testDeleteRemovesTheMapsAndMergesBeforeTheTag(): void
    {
        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ?', [666]],
                    ['DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` = ?', [666]],
                    ['DELETE FROM `tag` WHERE `tag`.`id` = ? ', [666]],
                )
            );

        $this->subject->delete(666);
    }

    public function testFindIdByNameReturnsNullWhenTheTagIsUnknown(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        static::assertNull($this->subject->findIdByName('some-tag'));
    }

    public function testGetSongTagNamesByAlbumReadsTheGenresOfItsSongs(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                "SELECT `tag`.`name` FROM `tag` JOIN `tag_map` ON `tag`.`id` = `tag_map`.`tag_id` JOIN `song` ON `tag_map`.`object_id` = `song`.`id` WHERE `song`.`album` = ? AND `tag_map`.`object_type` = 'song' GROUP BY `tag`.`id`, `tag`.`name`;",
                [666]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('Rock', false);

        static::assertSame(['Rock'], $this->subject->getSongTagNamesByAlbum(666));
    }

    public function testGetSongTagNamesByArtistGoesThroughTheArtistMap(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                static::stringContains("`song`.`id` IN (SELECT `object_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_type` = 'song')"),
                [42]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        static::assertSame([], $this->subject->getSongTagNamesByArtist(42));
    }

    public function testGetTagIdsAppendsAnOffsetOnlyAlongsideACount(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT DISTINCT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = ?  LIMIT 20, 10',
                ['song']
            )
            ->willReturn($result);

        $result->method('fetchColumn')->willReturn('666', false);

        static::assertSame([666], $this->subject->getTagIds('song', 10, 20));
    }

    public function testGetTagIdsOmitsTheLimitEntirelyWithoutACount(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT DISTINCT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = ? ', ['song'])
            ->willReturn($result);

        $result->method('fetchColumn')->willReturn(false);

        static::assertSame([], $this->subject->getTagIds('song', 0, 20));
    }

    public function testIncrementCountWritesTheColumnFromTheEnum(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `tag` SET `album` = `album` + 1 WHERE `id` = ?', [666]);

        $this->subject->incrementCount(666, TagCountTypeEnum::ALBUM);
    }

    public function testMapExistsCountsAMergedTagAsAMatch(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with(
                'SELECT * FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id` = `tag_map`.`tag_id` LEFT JOIN `tag_merge` ON `tag`.`id`=`tag_merge`.`tag_id` WHERE (`tag_map`.`tag_id` = ? OR `tag_map`.`tag_id` = `tag_merge`.`merged_to`) AND `tag_map`.`user` = ? AND `tag_map`.`object_id` = ? AND `tag_map`.`object_type` = ?',
                [666, 0, 42, 'song']
            )
            ->willReturn(['id' => '7']);

        static::assertTrue($this->subject->mapExists('song', 42, 666, 0));
    }

    public function testMapExistsReturnsFalseWithoutARow(): void
    {
        $this->connection->method('fetchRow')->willReturn(false);

        static::assertFalse($this->subject->mapExists('song', 42, 666, 0));
    }

    public function testMergeIntoBindsBothTagIdsRatherThanInterpolatingThem(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'REPLACE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) SELECT ?, `user`, `object_type`, `object_id` FROM `tag_map` AS `tm` WHERE `tm`.`tag_id` = ? AND NOT EXISTS (SELECT 1 FROM `tag_map` WHERE `tag_map`.`tag_id` = ? AND `tag_map`.`object_id` = `tm`.`object_id` AND `tag_map`.`object_type` = `tm`.`object_type` AND `tag_map`.`user` = `tm`.`user`)',
                [42, 666, 42]
            );

        $this->subject->mergeInto(666, 42);
    }

    public function testSetHiddenWritesTheFlagAloneOtherwise(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `tag` SET `is_hidden` = ? WHERE `id` = ?', [0, 666]);

        $this->subject->setHidden(666, 0, false);
    }

    public function testSetHiddenZeroesTheCountersOnlyWhenAsked(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `tag` SET `is_hidden` = ?, `artist` = 0, `album` = 0, `song` = 0 WHERE `id` = ?', [1, 666]);

        $this->subject->setHidden(666, 1, true);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->catalogCounter = $this->createMock(CatalogCounterInterface::class);

        $this->subject = new TagRepository(
            $this->connection,
            $this->catalogCounter
        );
    }
}
