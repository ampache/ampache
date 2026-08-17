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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Video;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class VideoRepositoryTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private ModelFactoryInterface&MockObject $modelFactory;
    private VideoRepository $subject;

    public function testCollectGarbageAppliesTheIgnorePattern(): void
    {
        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::CATALOG_IGNORE_PATTERN)
            ->willReturn('some-pattern');

        $matcher = static::exactly(2);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use ($matcher): PDOStatement {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertStringContainsString('`file` REGEXP ?', $sql);
                    self::assertSame(['some-pattern'], $params);
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage();
    }

    public function testCollectGarbageSkipsThePatternDeleteWhenNoneIsConfigured(): void
    {
        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::CATALOG_IGNORE_PATTERN)
            ->willReturn('');

        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('NOT IN (SELECT `id` FROM `catalog`)'));

        $this->subject->collectGarbage();
    }

    public function testDeleteByCatalogRemovesTheCatalogsVideos(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `video` WHERE `catalog` = ?', [7]);

        self::assertTrue($this->subject->deleteByCatalog(7));
    }

    public function testDeleteRecordsThenRemovesTheRow(): void
    {
        $video = $this->createMock(Video::class);

        $video->method('getId')
            ->willReturn(666);

        $matcher = static::exactly(2);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($matcher): PDOStatement {
                self::assertSame([666], $params);
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertStringContainsString('REPLACE INTO `deleted_video`', $sql);
                } else {
                    self::assertStringContainsString('DELETE FROM `video`', $sql);
                }

                return $this->createMock(PDOStatement::class);
            });

        self::assertTrue($this->subject->delete($video));
    }

    public function testDeleteReturnsFalseWhenTheWriteFailed(): void
    {
        $video = $this->createMock(Video::class);

        $video->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        self::assertFalse($this->subject->delete($video));
    }

    public function testFindByIdReturnsNullWhenTheVideoDoesNotExist(): void
    {
        $video = $this->createMock(Video::class);
        $video->method('isNew')->willReturn(true);

        $this->modelFactory->method('createVideo')->willReturn($video);

        self::assertNull($this->subject->findById(666));
    }

    public function testFindByIdReturnsTheLoadedVideo(): void
    {
        $video = $this->createMock(Video::class);
        $video->method('isNew')->willReturn(false);

        $this->modelFactory->expects(static::once())
            ->method('createVideo')
            ->with(666)
            ->willReturn($video);

        self::assertSame($video, $this->subject->findById(666));
    }

    public function testFindIdByFileReturnsNullWhenNoVideoHoldsIt(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `video` WHERE `file` = ?;', ['/media/none.mkv'])
            ->willReturn(false);

        self::assertNull($this->subject->findIdByFile('/media/none.mkv'));
    }

    public function testGetFilesByCatalogKeysTheFilesByVideoId(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id`, `file` FROM `video` WHERE `catalog` = ? AND `file` IS NOT NULL ORDER BY `id` DESC;',
                [7]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->willReturn(['id' => '3', 'file' => '/media/clip.mkv'], false);

        self::assertSame([3 => '/media/clip.mkv'], $this->subject->getFilesByCatalog(7));
    }

    public function testGetIdsByCatalogReadsTheCatalogsVideos(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` WHERE `video`.`catalog` = ?',
                [7]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('666', false);

        self::assertSame([666], $this->subject->getIdsByCatalog(7));
    }

    public function testGetIdsByFilePrefixBindsTheWildcard(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `video` WHERE `file` LIKE ?',
                ['/media/%']
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        self::assertSame([], $this->subject->getIdsByFilePrefix('/media/'));
    }

    public function testGetItemCountReturnsTheCount(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) AS `count` FROM `video`;')
            ->willReturn('42');

        self::assertSame(42, $this->subject->getItemCount());
    }

    public function testGetRowsByIdsCastsTheIdsIntoTheStatement(): void
    {
        $result = $this->createMock(PDOStatement::class);

        // the id list is interpolated rather than bound, so it has to be forced to int on the way in
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT * FROM `video` WHERE `video`.`id` IN (1,2)')
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        self::assertSame([], $this->subject->getRowsByIds(['1', '2abc']));
    }

    public function testGetRowsByIdsReturnsNothingForAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        self::assertSame([], $this->subject->getRowsByIds([]));
    }

    public function testInsertReturnsTheNewId(): void
    {
        $params = ['/some/file.mkv', 1, 'some-title'];

        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('INSERT INTO `video`'), $params);

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        self::assertSame(666, $this->subject->insert($params));
    }

    public function testPruneDeletedHistoryDeletesOlderRows(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `deleted_video` WHERE `delete_time` < (UNIX_TIMESTAMP() - (? * 86400));',
                [365]
            );

        $this->subject->pruneDeletedHistory(365);
    }

    public function testPruneDeletedHistorySkipsWhenDaysIsNotPositive(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->pruneDeletedHistory(0);
        $this->subject->pruneDeletedHistory(-1);
    }

    public function testSetFileStoresThePathTheVideoIsServedFrom(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `video` SET `file` = ? WHERE `id` = ?', ['/new/clip.mkv', 3]);

        $this->subject->setFile(3, '/new/clip.mkv');
    }

    public function testSetPlayedBindsTheFlagAsAnInt(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `video` SET `played` = ? WHERE `id` = ?', [0, 666]);

        $this->subject->setPlayed(666, false);
    }

    public function testSetUpdateTimeStampsTheVideo(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `video` SET `update_time` = ? WHERE `id` = ?;', [1234, 666]);

        $this->subject->setUpdateTime(666, 1234);
    }

    public function testUpdateAddsTheReleaseDateWhenAsked(): void
    {
        $video = new Video();

        $video->id           = 666;
        $video->title        = 'some-title';
        $video->release_date = 1234;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `video` SET `title` = ?, `release_date` = ? WHERE `id` = ?',
                ['some-title', 1234, 666]
            );

        $this->subject->update($video, true);
    }

    public function testUpdateAllCountsRunsEverySweepAndCarriesOnAfterAFailure(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(6))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;
                if (count($calls) === 1) {
                    throw new QueryFailedException('nope');
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->logger->expects(static::once())
            ->method('warning');

        $this->subject->updateAllCounts();

        foreach ($calls as $sql) {
            self::assertStringStartsWith('UPDATE `video`', $sql);
        }
    }

    public function testUpdateCountsRunsAllFourStatements(): void
    {
        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->with(self::anything(), [666, 666]);

        $this->subject->updateCounts(666);
    }

    public function testUpdateWritesTheTitleOnly(): void
    {
        $video = new Video();

        $video->id    = 666;
        $video->title = 'some-title';

        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `video` SET `title` = ? WHERE `id` = ?', ['some-title', 666]);

        $this->subject->update($video, false);
    }

    protected function setUp(): void
    {
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->subject = new VideoRepository(
            $this->connection,
            $this->configContainer,
            $this->modelFactory,
            $this->logger
        );
    }
}
