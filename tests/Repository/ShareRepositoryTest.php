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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ShareInterface;
use Ampache\Repository\Model\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class ShareRepositoryTest extends MockeryTestCase
{
    /** @var MockInterface|Connection */
    private MockInterface $database;

    private ShareRepository $subject;

    public function setUp(): void
    {
        $this->database = $this->mock(Connection::class);

        $this->subject = new ShareRepository(
            $this->database
        );
    }

    public function testGetListReturnsData(): void
    {
        $user   = $this->mock(User::class);
        $result = $this->mock(Result::class);

        $shareId = 666;
        $userId  = 42;

        $user->shouldReceive('has_access')
            ->with(AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();
        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `share` WHERE `user` = ?',
                [$userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $shareId, false);

        $this->assertSame(
            [$shareId],
            $this->subject->getList($user)
        );
    }

    public function testDeleteReturnsFalseOnDbError(): void
    {
        $user = $this->mock(User::class);

        $shareId = 666;
        $userId  = 42;

        $user->shouldReceive('has_access')
            ->with(AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();
        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `share` WHERE `id` = ? AND `user` = ?',
                [$shareId, $userId]
            )
            ->once()
            ->andThrow(new Exception());

        $this->assertFalse(
            $this->subject->delete($shareId, $user)
        );
    }

    public function testDeleteReturnsTrueAfterDeletion(): void
    {
        $user = $this->mock(User::class);

        $shareId = 666;

        $user->shouldReceive('has_access')
            ->with(AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `share` WHERE `id` = ?',
                [$shareId]
            )
            ->once();

        $this->assertTrue(
            $this->subject->delete($shareId, $user)
        );
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `share` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newObjectId, $objectType, $oldObjectId]
            )
            ->once();

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }

    public function testCollectGarbageDelets(): void
    {
        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < ?) OR (`max_counter` > 0 AND `counter` >= `max_counter`)',
                \Mockery::type('array')
            )
            ->once();

        $this->subject->collectGarbage();
    }

    public function testSaveAccessSaves(): void
    {
        $share = $this->mock(ShareInterface::class);

        $lastVisitDate = 666;
        $shareId       = 42;

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($shareId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `share` SET `counter` = (`counter` + 1), lastvisit_date = ? WHERE `id` = ?',
                [$lastVisitDate, $shareId]
            )
            ->once();

        $this->subject->saveAccess($share, $lastVisitDate);
    }

    public function testUpdateUpdates(): void
    {
        $share = $this->mock(ShareInterface::class);

        $maxCounter    = 666;
        $expire        = 42;
        $allowStream   = 33;
        $allowDownload = 44;
        $description   = 'some-description';
        $userId        = 21;
        $shareId       = 11;

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($shareId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `share` SET `max_counter` = ?, `expire_days` = ?, `allow_stream` = ?, `allow_download` = ?, `description` = ? WHERE `id` = ? AND `user` = ?',
                [
                    $maxCounter,
                    $expire,
                    $allowStream,
                    $allowDownload,
                    $description,
                    $shareId,
                    $userId
                ]
            )
            ->once();

        $this->subject->update(
            $share,
            $maxCounter,
            $expire,
            $allowStream,
            $allowDownload,
            $description,
            $userId
        );
    }
}
