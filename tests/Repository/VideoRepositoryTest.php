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

class VideoRepositoryTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DatabaseConnectionInterface&MockObject $connection;
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
                    static::assertStringContainsString('`file` REGEXP ?', $sql);
                    static::assertSame(['some-pattern'], $params);
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
            ->with(static::stringContains('NOT IN (SELECT `id` FROM `catalog`)'));

        $this->subject->collectGarbage();
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
                static::assertSame([666], $params);
                if ($matcher->numberOfInvocations() === 1) {
                    static::assertStringContainsString('REPLACE INTO `deleted_video`', $sql);
                } else {
                    static::assertStringContainsString('DELETE FROM `video`', $sql);
                }

                return $this->createMock(PDOStatement::class);
            });

        static::assertTrue($this->subject->delete($video));
    }

    public function testDeleteReturnsFalseWhenTheWriteFailed(): void
    {
        $video = $this->createMock(Video::class);

        $video->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertFalse($this->subject->delete($video));
    }

    public function testFindByIdReturnsNullWhenTheVideoDoesNotExist(): void
    {
        $video = $this->createMock(Video::class);
        $video->method('isNew')->willReturn(true);

        $this->modelFactory->method('createVideo')->willReturn($video);

        static::assertNull($this->subject->findById(666));
    }

    public function testFindByIdReturnsTheLoadedVideo(): void
    {
        $video = $this->createMock(Video::class);
        $video->method('isNew')->willReturn(false);

        $this->modelFactory->expects(static::once())
            ->method('createVideo')
            ->with(666)
            ->willReturn($video);

        static::assertSame($video, $this->subject->findById(666));
    }

    public function testGetItemCountReturnsTheCount(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) AS `count` FROM `video`;')
            ->willReturn('42');

        static::assertSame(42, $this->subject->getItemCount());
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

        static::assertSame([], $this->subject->getRowsByIds(['1', '2abc']));
    }

    public function testGetRowsByIdsReturnsNothingForAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        static::assertSame([], $this->subject->getRowsByIds([]));
    }

    public function testInsertReturnsTheNewId(): void
    {
        $params = ['/some/file.mkv', 1, 'some-title'];

        $this->connection->expects(static::once())
            ->method('query')
            ->with(static::stringContains('INSERT INTO `video`'), $params);

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        static::assertSame(666, $this->subject->insert($params));
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

    public function testUpdateCountsRunsAllFourStatements(): void
    {
        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->with(static::anything(), [666, 666]);

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

        $this->subject = new VideoRepository(
            $this->connection,
            $this->configContainer,
            $this->modelFactory
        );
    }
}
