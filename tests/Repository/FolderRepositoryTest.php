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
 */

namespace Ampache\Repository;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FolderRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private FolderRepository $subject;

    public function testCollectGarbageRunsCleanupQueries(): void
    {
        $this->connection->expects(static::atLeast(7))
            ->method('query');

        $this->subject->collectGarbage();
    }

    public function testCollectGarbageSwallowsDatabaseException(): void
    {
        $this->connection->method('query')
            ->willThrowException(new QueryFailedException('boom'));

        $this->subject->collectGarbage();

        $this->addToAssertionCount(1);
    }

    public function testGetAllReturnsFoldersKeyedById(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `name` FROM `folder`')
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                ['id' => '1', 'name' => 'Music'],
                ['id' => '2', 'name' => 'Podcasts'],
                false,
            );

        self::assertSame(
            [1 => 'Music', 2 => 'Podcasts'],
            $this->subject->getAll(),
        );
    }

    public function testGetByCatalogKeyedByPathNameLowercasesTheKey(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id`, `path_name` FROM `folder` WHERE `catalog` = ? AND `path_name` IS NOT NULL;',
                [7]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->willReturn(['id' => '4', 'path_name' => '/Music/Some Artist'], false);

        self::assertSame(
            ['/music/some artist' => 4],
            $this->subject->getByCatalogKeyedByPathName(7)
        );
    }

    /**
     * folder_map rows carry their own `catalog` column, so a folder listing must honour the opt-in
     * `catalog_filter` feature the same way every other browse type does -- folders were the one type
     * this filter never reached
     */
    public function testGetChildrenAppliesTheCatalogFilterWhenEnabled(): void
    {
        AmpConfig::set('catalog_filter', true, true);

        try {
            $result = $this->createMock(PDOStatement::class);

            $this->connection->expects(static::once())
                ->method('query')
                ->with(
                    self::stringContains('`folder_map`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map`'),
                    [666]
                )
                ->willReturn($result);

            $result->method('fetch')->willReturn(false);

            $this->subject->getChildren(666, 5);
        } finally {
            AmpConfig::set('catalog_filter', false, true);
        }
    }

    public function testGetChildrenDropsRowsWithAnUnknownObjectType(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('`folder_id` = ?'), [666])
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['object_id' => '21', 'object_type' => 'song'],
                ['object_id' => '33', 'object_type' => 'not-a-type'],
                false
            );

        self::assertSame(
            [['object_type' => LibraryItemEnum::SONG, 'object_id' => 21]],
            $this->subject->getChildren(666)
        );
    }

    public function testGetChildrenQueriesTheRootWhenNoIdIsGiven(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('`folder_id` IS NULL'))
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        self::assertSame([], $this->subject->getChildren(null));
    }

    public function testGetChildrenSkipsTheCatalogFilterByDefault(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::logicalNot(self::stringContains('catalog_filter_group_map')), [666])
            ->willReturn($result);

        $result->method('fetch')->willReturn(false);

        $this->subject->getChildren(666, 5);
    }

    public function testGetItemCountReturnsCount(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT COUNT(*) AS `count` FROM `folder`;')
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['count' => '5']);

        self::assertSame(5, $this->subject->getItemCount());
    }

    public function testGetItemCountReturnsZeroWhenNoRowFound(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->method('query')
            ->willReturn($result);

        $result->method('fetch')
            ->willReturn(false);

        self::assertSame(0, $this->subject->getItemCount());
    }

    public function testGetMediaCountCountsEverythingBelowTheFolder(): void
    {
        $folder = new Folder();

        $folder->id        = 666;
        $folder->path_name = '/some/path';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                self::stringContains('`folder_map`.`path_name` LIKE ?'),
                [666, '/some/path/%']
            )
            ->willReturn('42');

        self::assertSame(42, $this->subject->getMediaCount($folder));
    }

    public function testGetMediaCountReturnsZeroWhenNothingIsFound(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(false);

        self::assertSame(0, $this->subject->getMediaCount(new Folder()));
    }

    public function testGetMediasNarrowsToASingleTypeWhenAsked(): void
    {
        $folder = new Folder();

        $folder->id        = 666;
        $folder->path_name = '/some/path';

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::stringContains('`folder_map`.`object_type` = ?'),
                ['song', 666, '/some/path/%']
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        self::assertSame([], $this->subject->getMedias($folder, 'song'));
    }

    public function testGetNameByIdReturnsNullWhenThereIsNoSuchFolder(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(false);

        self::assertNull($this->subject->getNameById(666));
    }

    public function testGetNameByIdReturnsTheName(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `folder`.`name` AS `f_name` FROM `folder` WHERE `id` = ?;', [666])
            ->willReturn('some-name');

        self::assertSame('some-name', $this->subject->getNameById(666));
    }

    public function testGetObjectsAppliesTheCatalogFilterToTheRootListingWhenEnabled(): void
    {
        AmpConfig::set('catalog_filter', true, true);

        try {
            $result = $this->createMock(PDOStatement::class);

            $this->connection->expects(static::once())
                ->method('query')
                ->with(self::stringContains('`folder`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map`'))
                ->willReturn($result);

            $result->method('fetch')->willReturn(false);

            $this->subject->getObjects(null, 5);
        } finally {
            AmpConfig::set('catalog_filter', false, true);
        }
    }

    public function testGetObjectsListsTopLevelFoldersForTheRoot(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('`parent` IS NULL'))
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['object_id' => '21', 'object_type' => 'folder'],
                false
            );

        self::assertSame(
            [['object_type' => LibraryItemEnum::FOLDER, 'object_id' => 21]],
            $this->subject->getObjects(null)
        );
    }

    public function testHasChildrenReportsWhetherRowsExist(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ?;', [666])
            ->willReturn($result);

        $result->expects(static::once())
            ->method('rowCount')
            ->willReturn(2);

        self::assertTrue($this->subject->hasChildren(666));
    }

    public function testLookupByPathNameReturnsIdWhenFound(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `folder` WHERE `path_name` = ? AND `catalog` = ?',
                ['/music', 3],
            )
            ->willReturn('21');

        self::assertSame(21, $this->subject->lookupByPathName('/music', 3));
    }

    public function testLookupByPathNameReturnsMinusOneForBlankPath(): void
    {
        $this->connection->expects(static::never())
            ->method('fetchOne');

        self::assertSame(-1, $this->subject->lookupByPathName(''));
    }

    public function testLookupReturnsIdWhenFound(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `folder` WHERE `name` = ? AND `catalog` = ? AND `parent` = ?',
                ['Music', 3, 7],
            )
            ->willReturn('21');

        self::assertSame(21, $this->subject->lookup('Music', 3, 7));
    }

    public function testLookupReturnsMinusOneForBlankName(): void
    {
        $this->connection->expects(static::never())
            ->method('fetchOne');

        self::assertSame(-1, $this->subject->lookup(''));
    }

    public function testLookupReturnsZeroWhenNotFound(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `folder` WHERE `name` = ? AND `catalog` = ?',
                ['Music', 3],
            )
            ->willReturn(false);

        self::assertSame(0, $this->subject->lookup('Music', 3));
    }

    public function testMigrateObjectMovesTheMapRows(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `folder_map` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?;',
                [33, 21, 'song']
            );

        $this->subject->migrateObject('song', 21, 33);
    }

    public function testPersistInsertsANewFolderAndReturnsTheId(): void
    {
        $folder = new Folder();

        $folder->name          = 'some-name';
        $folder->catalog       = 2;
        $folder->user          = 42;
        $folder->addition_time = 1000;
        $folder->update_time   = 1234;
        $folder->path          = '1,2';
        $folder->path_name     = '/some/path';

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::stringContains('INSERT INTO `folder`'),
                ['some-name', 2, null, 42, 1000, 1234, '1,2', '/some/path']
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        self::assertSame(666, $this->subject->persist($folder));
    }

    public function testPersistUpdatesAnExistingFolderAndReturnsNull(): void
    {
        $folder = new Folder();

        $folder->id          = 666;
        $folder->name        = 'some-name';
        $folder->catalog     = 2;
        $folder->parent      = 21;
        $folder->update_time = 1234;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `folder` SET `name` = ?, `catalog` = ?, `parent` = ?, `update_time` = ? WHERE `id` = ?',
                ['some-name', 2, 21, 1234, 666]
            );

        $this->connection->expects(static::never())
            ->method('getLastInsertedId');

        self::assertNull($this->subject->persist($folder));
    }

    public function testUpdateFolderCountsGivesEveryAncestorTheTotalOfItsSubtree(): void
    {
        $direct = $this->createMock(PDOStatement::class);
        $tree   = $this->createMock(PDOStatement::class);
        $writes = [];

        $this->connection->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use ($direct, $tree, &$writes): PDOStatement {
                if (str_contains($sql, 'FROM `folder_map` AS `smap`')) {
                    return $direct;
                }

                if ($sql === 'SELECT `id`, `path` FROM `folder`;') {
                    return $tree;
                }

                if (str_contains($sql, 'SET `total_count` = ?, `total_skip` = ?')) {
                    $writes[(int) $params[2]] = [(int) $params[0], (int) $params[1]];
                }

                return $this->createMock(PDOStatement::class);
            });

        // only the two leaf folders hold media
        $direct->method('fetch')->willReturn(
            ['folder_id' => '4', 'total_count' => '2', 'total_skip' => '1'],
            ['folder_id' => '5', 'total_count' => '3', 'total_skip' => '0'],
            false
        );

        // 1 -> 2 -> 4 and 1 -> 2 -> 5, plus an empty sibling
        $tree->method('fetch')->willReturn(
            ['id' => '1', 'path' => ''],
            ['id' => '2', 'path' => '1'],
            ['id' => '3', 'path' => '1'],
            ['id' => '4', 'path' => '1,2'],
            ['id' => '5', 'path' => '1,2'],
            false
        );

        $this->subject->update_folder_counts();

        // Stats::count() increments every ancestor as a track plays, so the rebuild has to match that
        self::assertSame([2, 1], $writes[4]);
        self::assertSame([3, 0], $writes[5]);
        self::assertSame([5, 1], $writes[2]);
        self::assertSame([5, 1], $writes[1]);
        self::assertArrayNotHasKey(3, $writes);
    }

    public function testUpdateUtimeUsesProvidedTime(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `folder` SET `update_time` = ? WHERE `id` = ?;',
                [12345, 21],
            );

        $this->subject->update_utime(21, 12345);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new FolderRepository($this->connection, $this->logger);
    }
}
