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

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\UseractivityInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class UserActivityRepositoryTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    /** @var Connection|MockInterface */
    private MockInterface $connection;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    private UserActivityRepository $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->connection      = $this->mock(Connection::class);
        $this->logger          = $this->mock(LoggerInterface::class);

        $this->subject = new UserActivityRepository(
            $this->configContainer,
            $this->modelFactory,
            $this->connection,
            $this->logger
        );
    }

    public function testGetFriendsActivitiesReturnsData(): void
    {
        $userId     = 666;
        $limit      = 33;
        $since      = 42;
        $activityId = 21;

        $activity = $this->mock(UseractivityInterface::class);
        $result   = $this->mock(Result::class);

        $this->configContainer->shouldReceive('getPopularThreshold')
            ->with(10)
            ->once()
            ->andReturn($limit);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                sprintf(
                    'SELECT `user_activity`.`id` FROM `user_activity` INNER JOIN `user_follower` ON `user_follower`.`follow_user` = `user_activity`.`user` WHERE `user_follower`.`user` = ? AND `user_activity`.`activity_date` <= ? ORDER BY `user_activity`.`activity_date` DESC LIMIT %d',
                    $limit
                ),
                [$userId, $since]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $activityId, false);

        $this->modelFactory->shouldReceive('createUseractivity')
            ->with($activityId)
            ->once()
            ->andReturn($activity);

        $this->assertSame(
            [$activity],
            $this->subject->getFriendsActivities($userId, 0, $since)
        );
    }

    public function testGetActivitiesReturnsData(): void
    {
        $userId     = 666;
        $limit      = 33;
        $since      = 42;
        $activityId = 21;

        $activity = $this->mock(UseractivityInterface::class);
        $result   = $this->mock(Result::class);

        $this->configContainer->shouldReceive('getPopularThreshold')
            ->with(10)
            ->once()
            ->andReturn($limit);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                sprintf(
                    'SELECT `id` FROM `user_activity` WHERE `user` = ? AND `activity_date` <= ? ORDER BY `activity_date` DESC LIMIT %d',
                    $limit
                ),
                [$userId, $since]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $activityId, false);

        $this->modelFactory->shouldReceive('createUseractivity')
            ->with($activityId)
            ->once()
            ->andReturn($activity);

        $this->assertSame(
            [$activity],
            $this->subject->getActivities($userId, 0, $since)
        );
    }

    public function testDeleteByDateDeletes(): void
    {
        $date   = 666;
        $action = 'some-action';
        $userId = 42;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `user_activity` WHERE `activity_date` = ? AND `action` = ? AND `user` = ?',
                [$date, $action, $userId]
            )
            ->once();

        $this->subject->deleteByDate(
            $date,
            $action,
            $userId
        );
    }

    public function testCollectGarbageFailsOnUnsupportedType(): void
    {
        $type = 'foobar';

        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Garbage collect on type `%s` is not supported.', $type),
                [LegacyLogger::CONTEXT_TYPE => UserActivityRepository::class]
            )
            ->once();

        $this->subject->collectGarbage($type);
    }

    public function testCollectGarbageDeleteForSpecificType(): void
    {
        $type     = 'video';
        $objectId = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `user_activity` WHERE `object_type` = ? AND `object_id` = ?',
                [$type, $objectId]
            )
            ->once();

        $this->subject->collectGarbage($type, $objectId);
    }

    public function testCollectGarbageDeletesForallTypes(): void
    {
        $types = ['song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season'];

        foreach ($types as $type) {
            $this->connection->shouldReceive('executeQuery')
                ->with(
                    sprintf(
                        'DELETE FROM `user_activity` WHERE `object_type` = ? AND `user_activity`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`)',
                        $type,
                        $type
                    ),
                    [
                        $type
                    ]
                )
                ->once();
            $this->connection->shouldReceive('executeQuery')
                ->with(
                    'DELETE FROM `user_activity` WHERE `object_type` IN (\'album\', \'artist\')'
                )
                ->once();
        }

        $this->subject->collectGarbage();
    }

    public function testRegisterSongEntrySaves(): void
    {
        $userId     = 666;
        $action     = 'some-action';
        $objectType = 'some-type';
        $objectId   = 42;
        $date       = 123456;
        $songName   = 'some-song-name';
        $artistName = 'some-artist-name';
        $albumName  = 'some-album-name';
        $songMbId   = 'some-song-mbid';
        $artistMbId = 'some-artist-mbid';
        $albumMbId  = 'some-album-mbid';

        $sql = <<<SQL
            INSERT INTO `user_activity`
            (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_track`, `name_artist`, `name_album`, `mbid_track`, `mbid_artist`, `mbid_album`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [
                    $userId,
                    $action,
                    $objectType,
                    $objectId,
                    $date,
                    $songName,
                    $artistName,
                    $albumName,
                    $songMbId,
                    $artistMbId,
                    $albumMbId
                ]
            )
            ->once();

        $this->subject->registerSongEntry(
            $userId,
            $action,
            $objectType,
            $objectId,
            $date,
            $songName,
            $artistName,
            $albumName,
            $songMbId,
            $artistMbId,
            $albumMbId
        );
    }

    public function testRegisterGenericEntrySaves(): void
    {
        $userId     = 666;
        $action     = 'some-action';
        $objectType = 'some-type';
        $objectId   = 42;
        $date       = 123456;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)',
                [$userId, $action, $objectType, $objectId, $date]
            )
            ->once();

        $this->subject->registerGenericEntry(
            $userId,
            $action,
            $objectType,
            $objectId,
            $date
        );
    }

    public function testRegisterArtistEntrySaves(): void
    {
        $userId     = 666;
        $action     = 'some-action';
        $objectType = 'some-object-type';
        $objectId   = 42;
        $date       = 123456;
        $artistName = 'some-artist-name';
        $artistMbId = 'some-mbid';

        $sql = <<<SQL
        INSERT INTO `user_activity`
        (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `mbid_artist`)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [
                    $userId,
                    $action,
                    $objectType,
                    $objectId,
                    $date,
                    $artistMbId,
                ]
            )
            ->once();

        $this->subject->registerArtistEntry(
            $userId,
            $action,
            $objectType,
            $objectId,
            $date,
            $artistName,
            $artistMbId
        );
    }

    public function testRegisterAlbumEntrySaves(): void
    {
        $userId     = 666;
        $action     = 'some-action';
        $objectType = 'some-object-type';
        $objectId   = 42;
        $date       = 123456;
        $artistName = 'some-name';
        $albumName  = 'some-album-name';
        $artistMbId = 'some-artist-mbid';
        $albumMbId  = 'some-album-mbid';

        $sql = <<<SQL
        INSERT INTO `user_activity`
        (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `name_album`, `mbid_artist`, `mbid_album`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [
                    $userId,
                    $action,
                    $objectType,
                    $objectId,
                    $date,
                    $artistName,
                    $albumName,
                    $artistMbId,
                    $albumMbId
                ]
            )
            ->once();

        $this->subject->registerAlbumEntry(
            $userId,
            $action,
            $objectType,
            $objectId,
            $date,
            $artistName,
            $albumName,
            $artistMbId,
            $albumMbId
        );
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-object-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `user_activity` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newObjectId, $objectType, $oldObjectId]
            )
            ->once();

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }
}
